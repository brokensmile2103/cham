<?php

namespace App\Console\Commands;

use App\Models\Farm;
use App\Services\TaskScheduler;
use Illuminate\Console\Command;

class GenerateTasks extends Command
{
    protected $signature = 'cham:generate-tasks';

    protected $description = 'Sinh công việc từ các lịch định kỳ cho mọi trại đang hoạt động (an toàn khi chạy lặp lại)';

    public function handle(TaskScheduler $scheduler): int
    {
        $total = 0;

        Farm::query()->where('is_active', true)->chunkById(100, function ($farms) use ($scheduler, &$total) {
            foreach ($farms as $farm) {
                $total += $scheduler->generateForFarm($farm);
            }
        });

        $this->info("Đã sinh {$total} công việc mới.");

        return self::SUCCESS;
    }
}
