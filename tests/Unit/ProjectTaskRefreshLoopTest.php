<?php

namespace Tests\Unit;

use App\Models\ProjectTask;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * refreshLoop：重复周期 -> 下个周期时间
 *
 * 回归：Carbon 3 的 addDays() 不接受字符串，库里 loop 是 varchar（"90"），
 * 曾导致自定义天数周期在 LoopTask 里抛 TypeError 并引发无限复制。
 */
class ProjectTaskRefreshLoopTest extends TestCase
{
    private function task($loop, ?Carbon $startAt = null): ProjectTask
    {
        $task = new ProjectTask();
        $task->loop = $loop;
        $task->start_at = $startAt;
        return $task;
    }

    private function todayAt(string $time): Carbon
    {
        return Carbon::parse(date("Y-m-d {$time}"));
    }

    public function test_custom_days_accept_string_from_database(): void
    {
        $start = $this->todayAt('09:00:00');
        $task = $this->task('90', $start);

        $this->assertTrue($task->refreshLoop());
        $this->assertSame($start->clone()->addDays(90)->toDateTimeString(), Carbon::parse($task->loop_at)->toDateTimeString());
    }

    public function test_custom_days_accept_int_from_request(): void
    {
        $start = $this->todayAt('09:00:00');
        $task = $this->task(90, $start);

        $this->assertTrue($task->refreshLoop());
        $this->assertSame($start->clone()->addDays(90)->toDateTimeString(), Carbon::parse($task->loop_at)->toDateTimeString());
    }

    public function test_custom_days_without_start_time_use_today_as_base(): void
    {
        $task = $this->task('3');

        $this->assertTrue($task->refreshLoop());
        $this->assertSame(Carbon::today()->addDays(3)->toDateTimeString(), Carbon::parse($task->loop_at)->toDateTimeString());
    }

    public function test_past_start_time_is_rebased_to_today_keeping_time_of_day(): void
    {
        $task = $this->task('day', $this->todayAt('09:30:00')->subDays(5));

        $this->assertTrue($task->refreshLoop());
        $this->assertSame($this->todayAt('09:30:00')->addDay()->toDateTimeString(), Carbon::parse($task->loop_at)->toDateTimeString());
    }

    /**
     * 0 天会让新任务的 loop_at 等于自己的开始时间，立刻再次命中周期窗口，必须拒绝
     */
    #[DataProvider('invalidLoops')]
    public function test_invalid_loop_is_rejected_and_clears_loop_at(mixed $loop): void
    {
        $task = $this->task($loop, $this->todayAt('09:00:00'));
        $task->loop_at = $this->todayAt('09:00:00');

        $this->assertFalse($task->refreshLoop());
        $this->assertNull($task->loop_at);
    }

    public static function invalidLoops(): array
    {
        return [
            'zero string' => ['0'],
            'zero int' => [0],
            'zero padded' => ['00'],
            'negative' => ['-1'],
            'letters' => ['abc'],
            'decimal' => ['1.5'],
            'empty' => [''],
            'null' => [null],
        ];
    }

    #[DataProvider('presetLoops')]
    public function test_preset_loops(string $loop, callable $expected): void
    {
        $start = $this->todayAt('09:00:00');
        $task = $this->task($loop, $start);

        $this->assertTrue($task->refreshLoop());
        $this->assertSame($expected($start->clone())->toDateTimeString(), Carbon::parse($task->loop_at)->toDateTimeString());
    }

    public static function presetLoops(): array
    {
        return [
            'day' => ['day', fn(Carbon $c) => $c->addDay()],
            'weekdays' => ['weekdays', fn(Carbon $c) => $c->addWeekday()],
            'week' => ['week', fn(Carbon $c) => $c->addWeek()],
            'twoweeks' => ['twoweeks', fn(Carbon $c) => $c->addWeeks(2)],
            'month' => ['month', fn(Carbon $c) => $c->addMonth()],
            'year' => ['year', fn(Carbon $c) => $c->addYear()],
        ];
    }

    public function test_never_clears_loop_at_and_succeeds(): void
    {
        $task = $this->task('never', $this->todayAt('09:00:00'));
        $task->loop_at = $this->todayAt('09:00:00');

        $this->assertTrue($task->refreshLoop());
        $this->assertNull($task->loop_at);
    }
}
