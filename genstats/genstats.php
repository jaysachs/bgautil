<?php
/**
 *------
 * Copyright 2024 Jay Sachs <vagabond@covariant.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
 * Generate the Stats class via
 *
 *  $ php genstats.php gamename > modules/php/Stats.php
 *
 * (assumes you're in the main game directory where stats.json is located).
 *
 * The Stats.php file wil contain backed enums for each statistic defined
 * in stats.json, with the enum name derived from the stats.json identifier,
 * and with typed methods for the different kinds of stats (table / player,
 * int / float / bool).
 *
 *
 * Usage in game code
 * ==================
 *
 * Insert one line of configuration into the Game.php constructor:
 *
 *    public function __construct() {
 *        ...
 *        Stats::init($this);
 *        ...
 *    }
 *
 * Initialize stats in setupNewGame():
 *
 *    protected function setupNewGame($players, $options = []) {
 *        ...
 *        // Initialize all stats to "zero" values
 *        Stats::initAll(array_keys($players));
 *
 *        // Or, initialize each stat individually:
 *        Stats::PLAYER_MY_FLOAT_STAT->initAll(array_keys($players), 1.732);
 *        Stats::PLAYER_MY_BOOL_STAT->initAll(array_keys($players), true);
 *
 *        // Or, different value per player id:
 *        foreach ($players as $player_id => $player) {
 *            Stats::PLAYER_MY_INT_STAT->init($player_id, rand(0, 4));
 *        }
 *        // or, alternatively use initMap():
 *        Stats::PLAYER_MY_OTHER_INT_STAT->initMap($array_keys($players),
 *            function ($pid) { return rand(0, 4); });
 *
 *        // Table stats are simpler, only one possible init:
 *        Stats::TABLE_MY_FLOAT_STAT->init(3.14159);
 *        ...
 *     }
 *
 * Updating / accessing stats (anywhere):
 *
 *    ...
 *    Stats::PLAYER_NUMBER_TURNS->inc($player_id);
 *    Stats::TABLE_GAME_ENDED_DUE_TO_PIECE_EXHAUSTION->set(true);
 *    Stats::TABLE_OTHER_FLOAT->set(1.15 * Stats::TABLE_OTHER_FLOAT->get());
 *    ...
 *
 * TODO: improve toIdentifier.
 * TODO: determine if need to include the BGA license boilerplate
 *       in the generated code.
 * TODO: need to let the Stats class name be customized?
 */
declare(strict_types=1);

if (count($argv) != 2) {
    error_log("Usage: php gentstats.php gamename");
    exit(1);
}
$gamename = $argv[1];

function toIdentifier($name): string {
    return strtoupper($name);
};

function statsFor(string $t_or_p, string $type): array {
    static $payload = file_get_contents("stats.json");
    static $all_stats = json_decode($payload, true);

    @ $s = $all_stats[$t_or_p];
    if ($s === null) {
        return [];
    }

    $s = array_filter(
        $s,
        function ($v, $n) use ($type) { return $v["type"] == $type; },
        ARRAY_FILTER_USE_BOTH
    );

    $res = [];
    foreach (array_keys($s) as $n) {
        $res[$n] = toIdentifier($n);
    }
    return $res;
}

echo "<?";
echo "php\n";
?>
/**
 * DO NOT EDIT
 *
 * THIS FILE WAS GENERATED FROM stats.json by bgautil/genstats.php
 */
declare(strict_types=1);

namespace Bga\Games\<?php echo $gamename ?>;

interface StatsImpl {
    public function incStat(mixed $delta, string $name, ?int $player_id = null) : void;
    public function setStat(mixed $val, string $name, ?int $player_id = null) : void;
    public function getStat(string $name, ?int $player_id = null): mixed;
    public function initStat(string $type, string $name, mixed $index, ?int $player_id = null): void;
}

class IntPlayerStat {
    function __construct(private mixed $impl, public readonly string $index) {
    }

    /** @param int[] $player_ids */
    public function init(array $player_ids, int $val = 0): void {
        foreach ($player_ids as $player_id) {
            $this->impl->initStat("player", $this->index, $val, $player_id);
        }
    }

    /** @param int[] $player_ids */
    public function initMap(array $player_ids, \Closure $val): void {
        foreach ($player_ids as $player_id) {
            $this->impl->initStat("player", $this->index, $val($player_id), $player_id);
        }
    }

    public function inc(int $player_id, int $delta = 1): void {
        $this->impl->incStat($delta, $this->index, $player_id);
    }

    public function set(int $player_id, int $val): void {
        $this->impl->setStat($val, $this->index, $player_id);
    }

    public function get(int $player_id): int {
        return $this->impl->getStat($this->index, $player_id);
    }
}

class BoolPlayerStat {
    function __construct(private mixed $impl, public readonly string $index) {
    }

    /** @param int[] $player_ids */
    public function init(array $player_ids, bool $val = false): void {
        foreach ($player_ids as $player_id) {
            $this->impl->initStat("player", $this->index, $val, $player_id);
        }
    }

    public function set(int $player_id, bool $val): void {
        $this->impl->setStat($val, $this->index, $player_id);
    }

    public function get(int $player_id): bool {
        return $this->impl->getStat($this->index, $player_id);
    }

    /** @param int[] $player_ids */
    public function initMap(array $player_ids, \Closure $val): void {
        foreach ($player_ids as $player_id) {
            $this->impl->initStat("player", $this->index, $val($player_id), $player_id);
        }
    }
}

class FloatPlayerStat {
    function __construct(private mixed $impl, public readonly string $index) {
    }

    /** @param int[] $player_ids */
    public function init(array $player_ids, float $val = 0.0): void {
        foreach ($player_ids as $player_id) {
            $this->impl->initStat("player", $this->index, $val, $player_id);
        }
    }

    public function add(int $player_id, float $delta): void {
        $this->impl->incStat($delta, $this->index, $player_id);
    }

    public function set(int $player_id, float $val): void {
        $this->impl->setStat($val, $this->index, $player_id);
    }

    public function get(int $player_id): float {
        return $this->impl->getStat($this->index, $player_id);
    }

    /** @param int[] $player_ids */
    public function initMap(array $player_ids, \Closure $val): void {
        foreach ($player_ids as $player_id) {
            $this->impl->initStat("player", $this->index, $val($player_id), $player_id);
        }
    }
}

class IntTableStat {
    function __construct(private mixed $impl, public readonly string $index) {
    }

    public function init(int $val = 0): void {
        $this->impl->initStat("table", $this->index, $val);
    }

    public function inc(int $delta = 1): void {
        $this->impl->incStat($delta, $this->index);
    }

    public function set(int $val): void {
        $this->impl->setStat($val, $this->index);
    }

    public function get(): int {
        return $this->impl->getStat($this->index);
    }
}

class BoolTableStat {
    function __construct(private mixed $impl, public readonly string $index) {
    }

    public function init(bool $val = false): void {
        $this->impl->initStat("table", $this->index, $val);
    }

    public function set(bool $val): void {
        $this->impl->setStat($val, $this->index);
    }

    public function get(): bool {
        return $this->impl->getStat($this->index);
    }
}

class FloatTableStat {
    function __construct(private mixed $impl, public readonly string $index) {
    }

    public function init(float $val = 0.0): void {
        $this->impl->initStat("table", $this->index, $val);
    }

    public function add(float $delta): void {
        $this->impl->incStat($delta, $this->index);
    }

    public function set(float $val): void {
        $this->impl->setStat($val, $this->index);
    }

    public function get(): float {
        return $this->impl->getStat($this->index);
    }
}

class GameStatsImpl implements StatsImpl {
    public function __construct(private \Bga\GameFramework\Table $game) {}

    #[\Override]
    public function incStat(mixed $delta, string $name, ?int $player_id = null) : void {
        $this->game->incStat($delta, $name, $player_id);
    }

    #[\Override]
    public function setStat(mixed $val, string $name, ?int $player_id = null) : void {
        $this->game->setStat($val, $name, $player_id);
    }

    #[\Override]
    public function getStat(string $name, ?int $player_id = null): mixed {
        return $this->game->getStat($name, $player_id);
    }

    #[\Override]
    public function initStat(string $type, string $name, mixed $index, ?int $player_id = null): void {
        $this->game->initStat($type, $name, $index, $player_id);
    }
}

enum OpType: string
{
    case SET = 'SET';
    case INC = 'INC';
}

class StatOp {
    public function __construct(
        public readonly OpType $op_type,
        public readonly string $name,
        public readonly ?int $player_id,
        public readonly mixed $value
    ) {}

    /** @param array<int, StatOp> $statOps */
    public static function applyAllTo(StatsImpl $impl, array $statOps): void {
        foreach ($statOps as $op) {
            switch ($op->op_type) {
                case OpType::INC:
                    $impl->incStat($op->value, $op->name, $op->player_id);
                    break;
                case OpType::SET:
                    $impl->setStat($op->value, $op->name, $op->player_id);
                    break;
            }
        }
    }

}

class RecordingStatsImpl implements StatsImpl
{
    /** @var StatOp[] */
    private array $operations = [];

    public function __construct(
        private StatsImpl $impl
    ) {}

    /** @param StatOp[] ops */
    public function addOperations(array $ops): void {
        foreach ($ops as $op) {
            $this->operations[] = $op;
        }
    }

    /** @return StatOp[] */
    public function getOperations(): array {
        return $this->operations;
    }

    #[\Override]
    public function incStat(mixed $delta, string $name, ?int $player_id = null) : void {
        $this->operations[] = new StatOp(OpType::INC, $name, $player_id, $delta);
    }

    #[\Override]
    public function setStat(mixed $val, string $name, ?int $player_id = null) : void {
        $this->operations[] = new StatOp(OpType::SET, $name, $player_id, $val);
    }

    #[\Override]
    public function initStat(string $type, string $name, mixed $index, ?int $player_id = null): void {
        $this->impl->initStat($type, $name, $index, $player_id);
    }

    #[\Override]
    public function getStat(string $name, ?int $player_id = null): mixed {
        $val = $this->impl->getStat($name, $player_id);
        // Reflect all the operations going on here. Not optimized, should be rarely used.
        foreach ($this->operations as $_ => $op) {
            if ($op->player_id == $player_id) {
                switch ($op->op_type) {
                    case OpType::INC:
                        $val += $op->value; break;
                    case OpType::SET:
                        $val = $op->value; break;
                }
            }
        }
        return $val;
    }
}

class TestStatsImpl implements StatsImpl {
    public $vals = [];
    public function initStat(string $cat, string $name, mixed $value, ?int $player_id = 0): void {
        if ($player_id === null) {
            $this->vals[$name] = $value;
        } else {
            if (! isset($this->vals[$name])) {
                $this->vals[$name] = [];
            }
            $this->vals[$name][$player_id] = $value;
        }
    }

    public function incStat(mixed $delta, string $name, ?int $player_id = 0): void {
        if ($player_id === null) {
            $this->vals[$name] += $delta;
        } else {
            $this->vals[$name][$player_id] += $delta;
        }
    }

    public function setStat($value, $name, ?int $player_id = 0): void {
        if ($player_id === null) {
            $this->vals[$name] = $value;
        } else {
            $this->vals[$name][$player_id] = $value;
        }
    }

    public function getStat($name, ?int $player_id = 0): mixed {
        if ($player_id === null) {
            return $this->vals[$name];
        }
        return $this->vals[$name][$player_id];
    }
}

class Stats {

    public static function createForGame(\Bga\GameFramework\Table $game): Stats {
        return new Stats(new GameStatsImpl($game));
    }

    /** @param StatsImpl $impl */
    public function __construct(private StatsImpl $impl) {
<?php foreach (["player", "table"] as $scope) {
          foreach (["int", "float", "bool"] as $type) {
              foreach (statsFor($scope, $type) as $n => $id) {
                  $typename =  ucfirst($type) . ucfirst($scope);
                  $name = strtoupper($scope) . "_" . $id; ?>
        $this-><?php echo  $name ?> = new <?php echo  $typename ?>Stat($impl, "<?php echo $n ?>");
<?php         }
          }
      } ?>
    }

    /** @param int[] $player_ids */
    public function initAll(array $player_ids): void {
<?php foreach (["player", "table"] as $scope) {
          foreach (["int", "float", "bool"] as $type) {
              foreach (statsFor($scope, $type) as $n => $id) {
                  $typename =  ucfirst($type) . ucfirst($scope);
                  $name = strtoupper($scope) . "_" . $id; ?>
      $this-><?php echo $name ?>->init(<?php if ($scope == "player") { ?>$player_ids<?php } ?>);
<?php         }
          }
      } ?>
    }

<?php foreach (["player", "table"] as $scope) {
          foreach (["int", "float", "bool"] as $type) {
              foreach (statsFor($scope, $type) as $n => $id) {
                  $typename =  ucfirst($type) . ucfirst($scope);
                  $name = strtoupper($scope) . "_" . $id; ?>
    public readonly <?php echo $typename ?>Stat $<?php echo $name ?>;
<?php         }
          }
      } ?>
}
