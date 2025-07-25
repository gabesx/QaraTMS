<?php

namespace App\Models;

use App\Enums\TestRunCaseStatus;
use Illuminate\Database\Eloquent\Model;


/**
 * @mixin IdeHelperTestRun
 */
class TestRun extends Model
{
    /*
     * Data format
     *
     * [case_id => status]
     */
    public function getResults()
    {
        return (array) json_decode($this->data);
    }

    /*
     * $results is array [case_id => status]
     */
    public function saveResults($results)
    {
        $this->data = json_encode($results);
        $this->save();
    }

    public function getInitialData()
    {
        $testPlan = TestPlan::findOrFail($this->test_plan_id);
        $testCasesIds = explode(',', $testPlan->data);

        $testRunData = [];

        foreach ($testCasesIds as $testCaseId) {
            $testRunData[$testCaseId] = TestRunCaseStatus::TODO;
        }
        return json_encode($testRunData);
    }

    public function getChartData()
    {
        $results = $this->getResults();

        $totalTestCases = count($results) != 0 ? count($results) : 1;
        $passed = 0;
        $failed = 0;
        $blocked = 0;
        $todo = 0;
        $skipped = 0;
        foreach ($results as $testCaseId => $status) {

            if ($status == TestRunCaseStatus::PASSED) {
                $passed++;
            } elseif ($status == TestRunCaseStatus::FAILED) {
                $failed++;
            } elseif ($status == TestRunCaseStatus::BLOCKED) {
                $blocked++;
            } elseif ($status == TestRunCaseStatus::TODO) {
                $todo++;
            } elseif ($status == TestRunCaseStatus::SKIPPED) {
                $skipped++;
            }

        }

        // [number, percent]
        $chartData = [
            'passed' => [$passed, (100 / $totalTestCases) * $passed],
            'failed' => [$failed, (100 / $totalTestCases) * $failed],
            'blocked' => [$blocked, (100 / $totalTestCases) * $blocked],
            'todo' => [$todo, (100 / $totalTestCases) * $todo],
            'skipped' => [$skipped, (100 / $totalTestCases) * $skipped],
        ];

        return $chartData;
    }


    public function removeDeletedCasesFromResults()
    {
        $currentResults = $this->getResults();
        $planTestCasesIds = array_keys($this->getResults());

        $existingCasesIds = TestCase::whereIn('id', $planTestCasesIds)->get()->pluck('id')->toArray();

        foreach ($planTestCasesIds as $planTestCaseId) {

            if (false == in_array($planTestCaseId, $existingCasesIds)) {
                unset($currentResults[$planTestCaseId]);
            }
        }

        $this->saveResults($currentResults);
    }

    public function getAssignee()
    {
        return (array) json_decode($this->assignee);
    }
}
