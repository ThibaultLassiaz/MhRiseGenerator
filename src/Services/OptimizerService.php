<?php

namespace App\Services;

use App\Data\ArmorProvider;
use App\Data\BudgetDataProvider;
use App\Data\EnchantProvider;
use App\Enum\ArmorSkills;
use App\Enum\TalismanSkills;
use drupol\phpermutations\Generators\Permutations;

final class OptimizerService
{
    protected ArmorProvider $armorProvider;
    protected EnchantProvider $enchantProvider;

    public function __construct(ArmorProvider $armorProvider, EnchantProvider $enchantProvider)
    {
        $this->armorProvider = $armorProvider;
        $this->enchantProvider = $enchantProvider;
    }

    /**
     * @param array<string, mixed> $requiredSkills
     * @param array<mixed, mixed>  $skillBudget
     *
     * @return array<string, mixed>
     */
    public function generatePools(array $requiredSkills, array $skillBudget): array
    {
        $skillTab = [];
        foreach ($skillBudget as $skill) {
            if (array_key_exists($skill[2], $requiredSkills)) {
                $skillTab[$skill[1]][] = $skill[2];
            }
        }
        $skillTab['18'][] = 'Upgrade Slot 3';
        $skillTab['12'][] = 'Upgrade Slot 2';
        $skillTab['6'][] = 'Upgrade Slot 1';

        return $skillTab;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $requiredSkills
     *
     * @return array<string, mixed>
     */
    public function removeSkills(array $item, int $budget, array $requiredSkills): array
    {
        $operations = 0;
        $itemToAdd = [
            'itemName' => $item['name'],
            'skills' => [],
        ];

        foreach ($item['skills'] as $skillName => $value) {
            $counter = 0;
            // Skills we don't want, or maybe we can replace it with an excluded skills list
            if (
                in_array($skillName, $requiredSkills)
                || in_array($skillName, ['Stamina Surge', 'Constitution', 'Element Exploit'])
            ) {
                continue;
            }

            $counter = min($value, 3 - $operations);
            $operations += $counter;
            $budget += $counter * 10;

            if ($counter > 0) {
                $itemToAdd['skills'][$skillName] = '-'.$counter;
            }
        }

        switch ($operations) {
            case 0:
                $budget += 10;
                $itemToAdd['Armor'] = '-12';
                $itemToAdd['Res Elem'][] = '-3';
                $itemToAdd['Res Elem'][] = '-3';
                break;

            case 1:
                $budget += 8;
                $itemToAdd['Armor'] = '-12';
                $itemToAdd['Res Elem'][] = '-3';
                break;

            case 2:
                $budget += 5;
                $itemToAdd['Armor'] = '-12';
                break;

            default:
                break;
        }

        return [
            'itemToAdd' => $itemToAdd,
            'operations' => $operations,
            'budget' => $budget,
        ];
    }

    /**
     * @param array<int, mixed>    $upgradeValues
     * @param array<string,mixed>  $budgetAtm
     * @param array<string, mixed> $rep
     *
     * @return array<string, mixed>
     */
    public function combinationSum4(array $upgradeValues, int $sumSoFar, int $target, array $budgetAtm, int $nbSkilltoAdd, array &$rep = []): array
    {
        for ($i = $target; $i < count($upgradeValues); ++$i) {
            $budget = $budgetAtm;
            $sum = $sumSoFar;

            while ($sum > 0) {
                if (null !== $budget) {
                    $budget[] = $upgradeValues[$i];
                }

                $sum -= $upgradeValues[$i];
                if ($sum > 0) {
                    $this->combinationSum4($upgradeValues, $sum, $i + 1, $budget, $nbSkilltoAdd, $rep);
                }
            }

            if (0 == $sum && count($budget) <= $nbSkilltoAdd) {
                $rep[] = $budget;
            }
        }

        return $rep;
    }

    /**
     * @param array<mixed, mixed>  $combinations
     * @param array<mixed, mixed>  $possibleSlots
     * @param array<string, mixed> $skillTab
     * @param array<string, mixed> $itemSkills
     * @param array<string, mixed> $requiredSkills
     *
     * @return array<string, mixed>
     */
    public function deleteBadCombination(array $combinations, array $possibleSlots, array $skillTab, array $itemSkills, array $requiredSkills): array
    {
        $goodCombination = [];

        foreach ($combinations as $key => $combination) {
            // We only remove 18 here cause there is no skill that is 18 so only slots
            if (isset(array_count_values($combination)['18'])) {
                if (array_count_values($combination)['18'] > $possibleSlots['18']) {
                    continue;
                }
            }
            $goodCombination[] = $combination;
        }

        $combinations = [];
        foreach ($goodCombination as $skillBudget) {
            $tabToAdd = [];
            $flag = true;

            foreach ($skillBudget as $budgetValue) {
                if (3 == $budgetValue && !array_key_exists($budgetValue, $skillTab)) {
                    $flag = false;
                    break;
                }
                $tabToAdd[] = $skillTab[$budgetValue];
            }

            if ($flag) {
                $combinations = array_merge($combinations, $this->getCombinations($tabToAdd));
            }
        }

        $quriousItem['combinations'] = array_filter($combinations, function ($combination) use ($itemSkills, $requiredSkills) {
            $skills = array_count_values($combination);

            foreach ($skills as $name => $count) {
                if (array_key_exists($name, $itemSkills)) {
                    $maxSkillValue = $requiredSkills[$name];

                    if ($count + $itemSkills[$name] > $maxSkillValue) {
                        return false;
                    }
                }
            }

            return true;
        });

        return $quriousItem;
    }

    /**
     * @param array<int<0, max>, mixed> $arrays
     *
     * @return array<int<0, max>, array<int<0, max>, mixed>>
     */
    public function getCombinations(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property => $property_value]);
                }
            }
            $result = $tmp;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $slots
     */
    public function getPossibleSlot(array $slots, int $value): int
    {
        $filteredSlots = array_filter($slots, function ($slot) use ($value) {
            return $slot <= $value;
        });

        return count($filteredSlots);
    }

    public function closestMultiple(int $budget): float
    {
        return floor($budget / 3) * 3;
    }

    /**
     * @param array<int, mixed> $array
     *
     * @return array<int, mixed>
     */
    public function getPermutations(array $array): array
    {
        $permutations = new Permutations($array, 3);

        return array_unique($permutations->toArray(), SORT_REGULAR);
    }

    public function getCharms(): string
    {
        $charms = [];

        foreach (TalismanSkills::FIRST_SKILLS as $firstSkill) {
            foreach (TalismanSkills::SECOND_SKILLS as $secondSkill) {
                // We can't generate a charm with the same skill two times
                if (substr($firstSkill, 0, strpos($firstSkill, ',')) == substr($secondSkill, 0, strpos($secondSkill, ','))) {
                    continue;
                }

                $charms[] = $firstSkill.','.$secondSkill.',3, 1, 1';
                $charms[] = $firstSkill.','.$secondSkill.',4, 0, 0';
            }
        }

        return implode(PHP_EOL, $charms);
    }

    /**
     * @return array<mixed, mixed>
     */
    public function getArmors(): array
    {
        $finalData = [];
        $concat_values = [];
        $concat_pool = [];
        $armors = $this->armorProvider::ARMORS;
        $armorEnchants = $this->enchantProvider::ARMORS_ENCHANT;

        foreach ($armorEnchants as $item) {
            $concat_values[strtok($item[0], ' ')] = $item[3];
            $concat_pool[strtok($item[0], ' ')] = $item[2];
        }

        foreach ($armors as $armorPiece) {
            // Skip armors that have < 100 armor
            if ($armorPiece[12] < 100) {
                continue;
            }

            $finalData[] = [
                'name' => $armorPiece[0],
                'armor' => $armorPiece[12],
                'armorType' => $armorPiece[3],
                'resistance' => $armorPiece[13],
                'slots' => $armorPiece[10],
                'skills' => $armorPiece[11],
                'pool' => $concat_pool[strtok($armorPiece[0], ' ')],
                'budget' => $concat_values[strtok($armorPiece[0], ' ')],
            ];
        }

        return $finalData;
    }

    /**
     * @return array<mixed, mixed>
     */
    public function getQuriousArmors(): array
    {
        $result = [];

        $dataDecoded = $this->getArmors();
        $skillBudget = BudgetDataProvider::ENCHANTED_SKILLS;
        $skillTab = $this->generatePools(ArmorSkills::SKILLS, $skillBudget);

        foreach ($dataDecoded as $item) {
            $currentItem = $this->removeSkills($item, (int) $item['budget'], ArmorSkills::SKILLS);

            $upgradeValues = [18, 15, 12, 6, 3];
            if ($currentItem['budget'] % 3 !== 0) {
                $budget = $this->closestMultiple($currentItem['budget']);
            } else {
                $budget = $currentItem['budget'];
            }

            $currentItem['itemToAdd'][] = $budget;
            $itemToAdd = $currentItem['itemToAdd'];

            $possible18 = $this->getPossibleSlot($item['slots'], 1);
            $possible12 = $this->getPossibleSlot($item['slots'], 2);
            $possible6 = $this->getPossibleSlot($item['slots'], 3);

            $possibleSlots = [
                '18' => $possible18,
                '12' => $possible12,
                '6' => $possible6,
            ];

            $itemToAdd['slots'] = $item['slots'];
            $itemToAdd['possibleSlots'] = $possibleSlots;

            $maxRollCombinations = $this->combinationSum4($upgradeValues, (int) $budget, 0, [], 7 - $currentItem['operations']);
            $minRollCombinations = $this->combinationSum4($upgradeValues, (int) $budget - 6, 0, [], 7 - $currentItem['operations']);

            $itemsMaxRoll = $this->deleteBadCombination($maxRollCombinations, $possibleSlots, $skillTab, (array) $item['skills'], ArmorSkills::SKILLS);
            $itemsMinRoll = $this->deleteBadCombination($minRollCombinations, $possibleSlots, $skillTab, (array) $item['skills'], ArmorSkills::SKILLS);

            $arrayResultMerge = [];
            $itemCombinations = [$itemsMaxRoll['combinations'], $itemsMinRoll['combinations']];
            $resultMerge = array_merge($arrayResultMerge, ...$itemCombinations);
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
                $item['name'],
                $armor,
                $resElem[0],
                $resElem[1],
                $resElem[2],
                $resElem[3],
                $resElem[4],
            ];

            $uniqueSlots = count(array_unique($itemToAdd['slots']));

            $slotTypes = ['Upgrade Slot 3', 'Upgrade Slot 2', 'Upgrade Slot 1'];
            foreach ($itemToAdd['combinations'] as $combination) {
                $slotsToAdd = [];
                $slotUpgrade = 0;
                foreach ($slotTypes as $slotType) {
                    if (isset($combination[$slotType])) {
                        $possibleSlots = 0;

                        switch ($slotType) {
                            case 'Upgrade Slot 3':
                                $possibleSlots = $possible18;
                                break;
                            case 'Upgrade Slot 2':
                                $possibleSlots = $possible12;
                                break;
                            case 'Upgrade Slot 1':
                                $possibleSlots = $possible6;
                                break;
                        }

                        if ($combination[$slotType] <= $possibleSlots) {
                            $slotUpgrade += $combination[$slotType];
                            for ($i = 0; $i < $combination[$slotType]; ++$i) {
                                $slotsToAdd[] = (int) substr($slotType, -1);
                            }
                        }
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

                foreach ($combination as $skillToAdd => $value) {
                    if (!in_array($skillToAdd, ['Upgrade Slot 2', 'Upgrade Slot 1', 'Upgrade Slot 3'])) {
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

                if (1 === $uniqueSlots) {
                    foreach ($slotsToAdd as $slot) {
                        $output[] = $slot;
                    }
                } else {
                    $permutations = $this->getPermutations($slotsToAdd);
                    foreach ($permutations as $permutation) {
                        $output = array_merge($output, $permutation);
                    }
                }

                $result[] = $output;
            }
        }

        return $result;
    }
}
