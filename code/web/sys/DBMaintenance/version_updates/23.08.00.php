<?php
function getUpdates23_08_00(): array{
    $curTime = time();
    return [
        'add_supporting_company_system_variables' => [
                    'title' => 'Add supporting company into system variables',
                    'description' => 'Add column to set name of company undertaking installation',
                    'continueOnError' => false,
                    'sql' => [
                        "ALTER TABLE system_variables ADD COLUMN supportingCompany VARCHAR(72) default 'ByWater Solutions'",
                    ]
                ],
                //add_supporting_company_system_variables
    ];
}