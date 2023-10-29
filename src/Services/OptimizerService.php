<?php

namespace App\Services;

use App\Enum\Skills;
use drupol\phpermutations\Generators\Permutations;

final class OptimizerService
{
    public function openFile($fileName, $fileType, $path)
    {
        $filePath = $path.$fileName;

        if ('csv' == $fileType) {
            return array_map('str_getcsv', file($filePath));
        }

        return (array) json_decode(file_get_contents($filePath));
    }

    public function generatePools($requiredSkills, $skillBudget)
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

    public function removeSkills($item, $budget, $requiredSkills)
    {
        $operations = 0;
        $itemToAdd = [];
        $itemToAdd['itemName'] = $item->name;

        foreach ($item->skills as $skillName => $value) {
            $counter = 0;
            if (array_key_exists($skillName, $requiredSkills) || 'Stamina Surge' == $skillName || 'Constitution' == $skillName || 'Element Exploit' == $skillName) {
                continue;
            }

            for ($i = 0; $i < $value; ++$i) {
                if ($operations >= 3) {
                    continue;
                }
                ++$counter;
                ++$operations;
                $budget += 10;
            }

            if ($counter) {
                $itemToAdd['skills'][$skillName] = '-'.$counter;
            }
        }
        if (0 == $operations) {
            $budget += 10;
            $operations += 3;
            $itemToAdd['Armor'] = '-12';
            $itemToAdd['Res Elem'][] = '-3';
            $itemToAdd['Res Elem'][] = '-3';
        }

        if (1 == $operations) {
            $budget += 8;
            $operations += 2;
            $itemToAdd['Armor'] = '-12';
            $itemToAdd['Res Elem'][] = '-3';
        }

        if (2 == $operations) {
            $budget += 5;
            ++$operations;
            $itemToAdd['Armor'] = '-12';
        }

        $itemToAdd[] = $operations;
        $itemToAdd[] = $budget;

        return [
            'itemToAdd' => $itemToAdd,
            'operations' => $operations,
            'budget' => $budget,
        ];
    }

    public function combinationSum4($nums, $sumSoFar, $target, $puitAtm, $nbSkilltoAdd, &$rep = [])
    {
        for ($i = $target; $i < count($nums); ++$i) {
            $puit = $puitAtm;
            $sum = $sumSoFar;

            while ($sum > 0) {
                if (null !== $puit) {
                    $puit[] = $nums[$i];
                }

                $sum -= $nums[$i];
                if ($sum > 0) {
                    $this->combinationSum4($nums, $sum, $i + 1, $puit, $nbSkilltoAdd, $rep);
                }
            }

            if (0 == $sum && count($puit) <= $nbSkilltoAdd) {
                $rep[] = $puit;
            }
        }

        return $rep;
    }

    public function deleteBadCombination($combinations, $possibleSlots, $skillTab, $itemSkills, $requiredSkills)
    {
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
        foreach ($goodCombination as $skillPuit) {
            $tabToAdd = [];
            $flag = true;

            foreach ($skillPuit as $puitValue) {
                if (3 == $puitValue && !array_key_exists($puitValue, $skillTab)) {
                    $flag = false;
                    continue;
                }
                $tabToAdd[] = $skillTab[$puitValue];
            }

            if ($flag) {
                $combinations = array_merge($this->getCombinations($tabToAdd), $combinations);
            }
        }

        foreach ($combinations as $key => $combination) {
            $flag = false;
            $skills = array_count_values($combination);
            foreach ($skills as $name => $count) {
                if (array_key_exists($name, $itemSkills)) {
                    $maxSkillValue = $requiredSkills[$name];

                    if ($skills[$name] + $itemSkills[$name] > $maxSkillValue) {
                        $flag = true;
                    }
                }
            }
            if (!$flag) {
                $quriousItem['combinations'][] = $combination;
            }
        }

        return $quriousItem;
    }

    public function getCombinations($arrays)
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

    public function getPossibleSlot($slots, $value)
    {
        $possibleSlot = 0;

        foreach ($slots as $slot) {
            if ($slot <= $value) {
                ++$possibleSlot;
            }
        }

        return $possibleSlot;
    }

    public function closestMultiple(int $n, int $x = 3)
    {
        if ($x > $n) {
            return $x;
        }

        $n += intdiv($x, 2);
        $n -= ($n % $x);

        return $n;
    }

    public function imitateMerge($array1, $array2)
    {
        foreach ($array2 as $i) {
            foreach ($i as $array) {
                $array1[] = $array;
            }
        }

        return $array1;
    }

    public function getPermutations($array)
    {
        $permutations = new Permutations($array, 3);

        return array_unique($permutations->toArray(), SORT_REGULAR);
    }

    public function getCharms(): string
    {
        $charms = [];

        foreach (Skills::FIRST_SKILLS as $firstSkill) {
            foreach (Skills::SECOND_SKILLS as $secondSkill) {
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
}
