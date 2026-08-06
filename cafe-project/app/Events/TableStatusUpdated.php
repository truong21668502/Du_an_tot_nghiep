<?php
namespace App\Events;

use App\Models\Table;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $table;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function broadcastOn()
    {
        return new Channel('cafe-tables');
    }

    public function broadcastAs()
    {
        return 'TableUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->table->id,
            'status' => $this->table->status,
            'capacity' => $this->table->capacity,
            'parent_table_id' => $this->table->parent_table_id,
        ];
    }
}