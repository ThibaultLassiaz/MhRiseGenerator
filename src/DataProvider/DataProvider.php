<?php

namespace App\DataProvider;

final class DataProvider
{
    private const ARMOR_FILE_PATH = __DIR__.'/Data/Armors.json';
    private const SKILLS_BUDGET_PATH = __DIR__.'/Data/SkillsBudget.json';
    private const ENCHANTING_BUDGET_PATH = __DIR__.'/Data/EnchantingBudget.json';

    /**
     * Retrieve file content based on the file path constant.
     *
     * @param string $filePathConst the constant that holds the path to the JSON file
     *
     * @return array the decoded JSON content
     *
     * @throws \RuntimeException if the file cannot be read or decoded
     */
    public static function getDataFromFile(string $filePathConst): array
    {
        // Check if the constant exists and is a valid file path
        if (!defined("self::$filePathConst")) {
            throw new \InvalidArgumentException("Invalid file path constant: $filePathConst");
        }

        $filePath = constant("self::$filePathConst");

        $fileData = file_get_contents($filePath);

        if (false === $fileData) {
            throw new \RuntimeException("Could not read the file at $filePath.");
        }

        $data = json_decode($fileData, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Error decoding the JSON data: '.json_last_error_msg());
        }

        return $data;
    }

    public static function getArmors(): array
    {
        return self::getDataFromFile(self::ARMOR_FILE_PATH);
    }

    public static function getSkillsBudget(): array
    {
        return self::getDataFromFile(self::SKILLS_BUDGET_PATH);
    }

    public static function getEnchantingBudget(): array
    {
        return self::getDataFromFile(self::ENCHANTING_BUDGET_PATH);
    }
}
