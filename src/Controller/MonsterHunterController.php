<?php

namespace App\Controller;

use App\Data\ArmorProvider;
use App\Enum\ArmorSkills;
use App\Services\OptimizerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class MonsterHunterController extends AbstractController
{
    protected $mhservice;
    protected $optimizerService;

    public function __construct(ArmorProvider $mhservice, OptimizerService $optimizerService)
    {
        $this->mhservice = $mhservice;
        $this->optimizerService = $optimizerService;
    }

    /**
     * @Route("/generate_qurious", name="generate_qurious")
     */
    public function generateArmors()
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $mhDir = $projectDir.'\src\Files\MH\\';
        $outputDir = $projectDir.'\src\Output\\';
        $fp = fopen($outputDir.'output.txt', 'w');

        $skillBudget = $this->optimizerService->openFile('puits_skills.csv', 'csv', $mhDir);
        $dataDecoded = $this->optimizerService->openFile('armors_full.json', 'json', $mhDir);
        // $dataDecoded = $this->optimizerService->openFile('test.json', 'json', $mhDir);
        $skillTab = $this->optimizerService->generatePools(ArmorSkills::SKILLS, $skillBudget);

        foreach ($dataDecoded as $item) {
            $currentItem = $this->optimizerService->removeSkills($item, (int) $item->budget, ArmorSkills::SKILLS);
            $itemCombinations = [];

            $array = [18, 15, 12, 6, 3];
            if ($currentItem['budget'] % 3 !== 0) {
                $budget = $this->optimizerService->closestMultiple($currentItem['budget']);
            } else {
                $budget = $currentItem['budget'];
            }

            $currentItem['itemToAdd'][] = $budget;
            $itemToAdd = $currentItem['itemToAdd'];

            $possible6 = $this->optimizerService->getPossibleSlot($item->slots, 3);
            $possible12 = $this->optimizerService->getPossibleSlot($item->slots, 2);
            $possible18 = $this->optimizerService->getPossibleSlot($item->slots, 1);

            $possibleSlots = [
                '18' => $possible18,
                '12' => $possible12,
                '6' => $possible6,
            ];

            $itemToAdd['slots'] = $item->slots;
            $itemToAdd['possibleSlots'] = $possibleSlots;
            $maxRollCombinations = $this->optimizerService->combinationSum4($array, $budget, 0, [], 7 - $currentItem['operations']);
            $minRollCombinations = $this->optimizerService->combinationSum4($array, $budget - 6, 0, [], 7 - $currentItem['operations']);
            $itemsMaxRoll = $this->optimizerService->deleteBadCombination($maxRollCombinations, $possibleSlots, $skillTab, (array) $item->skills, ArmorSkills::SKILLS);
            $itemsMinRoll = $this->optimizerService->deleteBadCombination($minRollCombinations, $possibleSlots, $skillTab, (array) $item->skills, ArmorSkills::SKILLS);

            $arrayResultMerge = [];
            $itemCombinations = [$itemsMaxRoll['combinations'], $itemsMinRoll['combinations']];
            $resultMerge = $this->optimizerService->imitateMerge($arrayResultMerge, $itemCombinations);
            foreach ($resultMerge as $combination) {
                $itemToAdd['combinations'][] = array_count_values($combination);
            }

            $armor = 0;
            $resElem = [0, 0, 0, 0, 0];
            if (isset($itemToAdd['Res Elem'])) {
                for ($i = 0; $i < count($itemToAdd['Res Elem']); ++$i) {
                    $resElem[$i] = $itemToAdd['Res Elem'][$i];
                }
            }

            if (isset($itemToAdd['Armor'])) {
                $armor = $itemToAdd['Armor'];
            }

            $finalJson = [
                $item->name,
                $armor,
                $resElem[0],
                $resElem[1],
                $resElem[2],
                $resElem[3],
                $resElem[4],
            ];

            $uniqueSlots = count(array_unique($itemToAdd['slots']));

            foreach ($itemToAdd['combinations'] as $combination) {
                $slotsToAdd = [];
                $slotUpgrade = 0;
                if (isset($combination['Upgrade Slot 3'])) {
                    if ($combination['Upgrade Slot 3'] > $possible18) {
                        continue;
                    }
                    $slotUpgrade = $combination['Upgrade Slot 3'];
                    for ($i = 0; $i < $combination['Upgrade Slot 3']; ++$i) {
                        $slotsToAdd[] = 3;
                    }
                }
                if (isset($combination['Upgrade Slot 2'])) {
                    if ($combination['Upgrade Slot 2'] > $possible12) {
                        continue;
                    }
                    $slotUpgrade += $combination['Upgrade Slot 2'];
                    for ($i = 0; $i < $combination['Upgrade Slot 2']; ++$i) {
                        $slotsToAdd[] = 2;
                    }
                }
                if (isset($combination['Upgrade Slot 1'])) {
                    if ($combination['Upgrade Slot 1'] > $possible6) {
                        continue;
                    }
                    $slotUpgrade += $combination['Upgrade Slot 1'];
                    for ($i = 0; $i < $combination['Upgrade Slot 1']; ++$i) {
                        $slotsToAdd[] = 1;
                    }
                }

                if (!$slotUpgrade) {
                    continue;
                }

                for ($i = 0; $i < 3; ++$i) {
                    if (!isset($slotsToAdd[$i])) {
                        $slotsToAdd[] = 0;
                    }
                }

                $output = $finalJson;

                if (1 === $uniqueSlots) {
                    for ($i = 0; $i < count($slotsToAdd); ++$i) {
                        $output[] = $slotsToAdd[$i];
                    }

                    foreach ($combination as $skillToAdd => $value) {
                        if ('Upgrade Slot 2' !== $skillToAdd && 'Upgrade Slot 1' !== $skillToAdd && 'Upgrade Slot 3' !== $skillToAdd) {
                            $output[] = $skillToAdd;
                            $output[] = $value;
                        }
                    }

                    if (isset($itemToAdd['skills'])) {
                        foreach ($itemToAdd['skills'] as $skillToRemove => $value) {
                            $output[] = $skillToRemove;
                            $output[] = $value;
                        }
                    }

                    fwrite($fp, implode(',', $output).PHP_EOL);
                } else {
                    $permutations = $this->optimizerService->getPermutations($slotsToAdd);

                    foreach ($permutations as $permutation) {
                        $output = $finalJson;
                        $output[] = $permutation[0];
                        $output[] = $permutation[1];
                        $output[] = $permutation[2];

                        foreach ($combination as $skillToAdd => $value) {
                            if ('Upgrade Slot 2' !== $skillToAdd && 'Upgrade Slot 1' !== $skillToAdd && 'Upgrade Slot 3' !== $skillToAdd) {
                                $output[] = $skillToAdd;
                                $output[] = $value;
                            }
                        }

                        if (isset($itemToAdd['skills'])) {
                            foreach ($itemToAdd['skills'] as $skillToRemove => $value) {
                                // dd($skillToRemove);
                                $output[] = $skillToRemove;
                                $output[] = $value;
                            }
                        }

                        fwrite($fp, implode(',', $output).PHP_EOL);
                    }
                }
            }
        }
        fclose($fp);
    }
}
