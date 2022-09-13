<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\MHService;
use App\Services\OptimizerService;

final class MonsterHunterController extends AbstractController
{

    protected $mhservice;
    protected $optimizerService;

    const REQUIRED_SKILLS = [
        "Constitution" => 5,
        "Stamina Surge" => 3,
        "Attack Boost" => 7,
        "Chain Crit" => 3,
        "Critical Boost" => 3,
        "Critical Eye" => 7,
        "Weakness Exploit" => 3,
        "Spread Up" => 3,
        "Evade Window" => 5,
        "Divine Blessing" => 3,
        "Normal/Rapid Up" => 3,
        "Pierce Up" => 3,
        "Fire Attack" => 5,
        "Water Attack" => 5,
        "Ice Attack" => 5,
        "Thunder Attack" => 5,
        "Dragon Attack" => 5,
        "Flinch Free" => 1,
        "Reload Speed" => 2,
        "Guts" => 1,
    ];

    public function __construct(MHService $mhservice, OptimizerService $optimizerService)
    {
        $this->mhservice = $mhservice;
        $this->optimizerService = $optimizerService;
    }

    /**
     * @Route("/qurious", name="qurious")
     */
    public function getArmors()
    {
        $array = $this->mhservice->getData();

        $projectDir = $this->getParameter('kernel.project_dir');
        $mhDir = $projectDir . '\src\Files\MH\\';
        $puitsFile = $mhDir . 'puits.csv';

        $csv = array_map('str_getcsv', file($puitsFile));

        foreach ($csv as $armorFamily) {
            $concat_values[strtok($armorFamily[0],  ' ')] = $armorFamily[3];
            $concat_pool[strtok($armorFamily[0],  ' ')] = $armorFamily[2];
            $concat[] = strtok($armorFamily[0],  ' ');
        }


        foreach ($array as $armorPiece) {

            if ($armorPiece[12] < 100) {
                continue;
            }

            $final_data[] = [
                "name" => $armorPiece[0],
                "armor" => $armorPiece[12],
                "armorType" => $armorPiece[3],
                "resistance" => $armorPiece[13],
                "slots" => $armorPiece[10],
                "skills" => $armorPiece[11],
                "pool" => $concat_pool[strtok($armorPiece[0],  ' ')],
                "budget" => $concat_values[strtok($armorPiece[0],  ' ')]
            ];
        }

        $allStreamDataJson = json_encode($final_data);

        return new Response($allStreamDataJson);
    }

    /**
     * @Route("/generate_qurious", name="generate_qurious")
     */
    public function generateArmors()
    {

        $projectDir = $this->getParameter('kernel.project_dir');
        $mhDir = $projectDir . '\src\Files\MH\\';
        $outputDir = $projectDir . '\src\Output\\';
        $fp = fopen($outputDir . 'output.txt', 'w');

        $skillBudget = $this->optimizerService->openFile('puits_skills.csv', 'csv', $mhDir);
        $dataDecoded = $this->optimizerService->openFile('armors_full.json', 'json', $mhDir);
        // $dataDecoded = $this->optimizerService->openFile('test.json', 'json', $mhDir);
        $skillTab = $this->optimizerService->generatePools($this::REQUIRED_SKILLS, $skillBudget);

        foreach ($dataDecoded as $item) {

            $currentItem = $this->optimizerService->removeSkills($item, (int)$item->budget, $this::REQUIRED_SKILLS);
            $itemCombinations = [];

            $array = [18, 15, 12, 6, 3];
            if ($currentItem["budget"] % 3 !== 0) {
                $budget = $this->optimizerService->closestMultiple($currentItem["budget"]);
            } else {
                $budget = $currentItem["budget"];
            }

            $currentItem["itemToAdd"][] = $budget;
            $itemToAdd = $currentItem["itemToAdd"];

            $possible6 = $this->optimizerService->getPossibleSlot($item->slots, 3);
            $possible12 = $this->optimizerService->getPossibleSlot($item->slots, 2);
            $possible18 = $this->optimizerService->getPossibleSlot($item->slots, 1);

            $possibleSlots = [
                "18" => $possible18,
                "12" =>  $possible12,
                "6" =>  $possible6
            ];

            $itemToAdd["slots"] = $item->slots;
            $itemToAdd["possibleSlots"] = $possibleSlots;
            $maxRollCombinations = $itemToAdd["maxRoll"] = $this->optimizerService->combinationSum4($array, $budget, 0, [], (7 - $currentItem["operations"]));
            $minRollCombinations = $itemToAdd["maxRoll"] = $this->optimizerService->combinationSum4($array, $budget - 6, 0, [], (7 - $currentItem["operations"]));
            $itemsMaxRoll = $this->optimizerService->deleteBadCombination($maxRollCombinations, $possibleSlots, $skillTab, (array)$item->skills, $this::REQUIRED_SKILLS);
            $itemsMinRoll = $this->optimizerService->deleteBadCombination($minRollCombinations, $possibleSlots, $skillTab, (array)$item->skills, $this::REQUIRED_SKILLS);

            $arrayResultMerge = [];
            $itemCombinations = [$itemsMaxRoll["combinations"], $itemsMinRoll["combinations"]];
            $resultMerge = $this->optimizerService->imitateMerge($arrayResultMerge, $itemCombinations);
            foreach ($resultMerge as $combination) {
                $itemToAdd["combinations"][] = array_count_values($combination);
            }

            $armor = 0;
            $resElem = [0, 0, 0, 0, 0];
            if (isset($itemToAdd["Res Elem"])) {
                for ($i = 0; $i < count($itemToAdd["Res Elem"]); $i++) {
                    $resElem[$i] = $itemToAdd["Res Elem"][$i];
                }
            }

            if (isset($itemToAdd["Armor"])) {
                $armor = $itemToAdd["Armor"];
            }

            $finalJson = [
                $item->name,
                $armor,
                $resElem[0],
                $resElem[1],
                $resElem[2],
                $resElem[3],
                $resElem[4]
            ];

            $uniqueSlots = count(array_unique($itemToAdd["slots"]));

            foreach ($itemToAdd["combinations"] as $combination) {
                $slotsToAdd = [];
                $slotUpgrade = 0;
                if (isset($combination["Upgrade Slot 3"])) {
                    if ($combination["Upgrade Slot 3"] > $possible18) {
                        continue;
                    }
                    $slotUpgrade = $combination["Upgrade Slot 3"];
                    for ($i = 0; $i < $combination["Upgrade Slot 3"]; $i++) {
                        $slotsToAdd[] = 3;
                    }
                }
                if (isset($combination["Upgrade Slot 2"])) {
                    if ($combination["Upgrade Slot 2"] > $possible12) {
                        continue;
                    }
                    $slotUpgrade += $combination["Upgrade Slot 2"];
                    for ($i = 0; $i < $combination["Upgrade Slot 2"]; $i++) {
                        $slotsToAdd[] = 2;
                    }
                }
                if (isset($combination["Upgrade Slot 1"])) {
                    if ($combination["Upgrade Slot 1"] > $possible6) {
                        continue;
                    }
                    $slotUpgrade += $combination["Upgrade Slot 1"];
                    for ($i = 0; $i < $combination["Upgrade Slot 1"]; $i++) {
                        $slotsToAdd[] = 1;
                    }
                }

                if (!$slotUpgrade) {
                    continue;
                }

                for ($i = 0; $i < 3; $i++) {
                    if (!isset($slotsToAdd[$i])) {
                        $slotsToAdd[] = 0;
                    }
                }

                $output = $finalJson;

                if ($uniqueSlots === 1) {
                    for ($i = 0; $i < count($slotsToAdd); $i++) {
                        $output[] = $slotsToAdd[$i];
                    }

                    foreach ($combination as $skillToAdd => $value) {
                        if ($skillToAdd !== "Upgrade Slot 2" &&  $skillToAdd !== "Upgrade Slot 1" && $skillToAdd !== "Upgrade Slot 3") {
                            $output[] = $skillToAdd;
                            $output[] = $value;
                        }
                    }

                    if (isset($itemToAdd["skills"])) {
                        foreach ($itemToAdd["skills"] as $skillToRemove => $value) {
                            $output[] = $skillToRemove;
                            $output[] = $value;
                        }
                    }

                    fwrite($fp, implode(',', $output) . PHP_EOL);
                } else {
                    $permutations = $this->optimizerService->getPermutations($slotsToAdd);

                    foreach ($permutations as $permutation) {
                        $output = $finalJson;
                        $output[] = $permutation[0];
                        $output[] = $permutation[1];
                        $output[] = $permutation[2];

                        foreach ($combination as $skillToAdd => $value) {
                            if ($skillToAdd !== "Upgrade Slot 2" &&  $skillToAdd !== "Upgrade Slot 1" && $skillToAdd !== "Upgrade Slot 3") {
                                $output[] = $skillToAdd;
                                $output[] = $value;
                            }
                        }

                        if (isset($itemToAdd["skills"])) {
                            
                            foreach ($itemToAdd["skills"] as $skillToRemove => $value) {
                                // dd($skillToRemove);
                                $output[] = $skillToRemove;
                                $output[] = $value;
                            }
                        }

                        fwrite($fp, implode(',', $output) . PHP_EOL);
                    }
                }
            }
        }
        fclose($fp);
    }

    /**
     * @Route("/generate_charms", name="generate_charms")
     */
    public function getCartesian()
    {

        $projectDir = $this->getParameter('kernel.project_dir');
        $outputDir = $projectDir . '\src\Output\\';
        $fp = fopen($outputDir . 'output.txt', 'w');

        $skills = [
            'skill1' => [
                'Attack Boost,3',
                'Spread Up,2',
                'Normal/Rapid Up,2',
                'Pierce Up,2',
                'Constitution,3',
                'Weakness Exploit,2',
                'Critical Boost,2',
                'Stamina Surge,2',
                'Critical Eye,3',
                'Chain Crit,2',
                'Agitator,3',
                'Divine Blessing, 3',
                'Evade Window, 3'
            ],
            'skill2' => [
                'Attack Boost,2',
                'Spread Up,1',
                'Normal/Rapid Up,2',
                'Pierce Up,1',
                'Constitution,3',
                'Weakness Exploit,2',
                'Critical Boost,1',
                'Stamina Surge,2',
                'Critical Eye,2',
                'Chain Crit,2',
                'Agitator,2',
                'Divine Blessing, 2',
                'Evade Window, 2'
            ]
        ];

        foreach ($skills['skill1'] as $skill1) {
            foreach ($skills['skill2'] as $skill2) {
                if (substr($skill1, 0, strpos($skill1, ',')) == substr( $skill2, 0, strpos($skill2, ','))) {
                    continue;
                }
                $charm1 = $skill1 . ',' . $skill2 . ',3, 1, 1' . PHP_EOL;
                $charm2 = $skill1 . ',' . $skill2 . ',4, 0, 0' . PHP_EOL;

                fwrite($fp, $charm1 . PHP_EOL);
                fwrite($fp, $charm2 . PHP_EOL);
            }
        }

        fclose($fp);
    }
}
