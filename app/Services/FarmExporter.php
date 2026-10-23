<?php

namespace App\Services;

use App\Models\Farm;

/** Xuất toàn bộ dữ liệu của 1 trại ra mảng (JSON) — "dữ liệu của bạn thuộc về bạn". */
class FarmExporter
{
    public function export(Farm $farm): array
    {
        $take = fn ($relation, array $with = []) => $farm->{$relation}()->with($with)->get()->toArray();

        return [
            'app' => 'Chăm',
            'exported_at' => now()->toIso8601String(),
            'farm' => $farm->only(['id', 'name', 'address', 'created_at']),
            'species' => $take('species'),
            'breeds' => $take('breeds'),
            'zones' => $take('zones'),
            'batches' => $take('batches'),
            'inventory_items' => $farm->items()->withTrashed()->get()->toArray(),
            'stock_movements' => \App\Models\StockMovement::query()->where('farm_id', $farm->id)->get()->toArray(),
            'events' => $take('events'),
            'sales' => $take('sales'),
            'expenses' => $take('expenses'),
            'task_templates' => $take('taskTemplates'),
            'tasks' => $take('tasks'),
        ];
    }
}
