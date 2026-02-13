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
 *    private Stats $stats;
 *
 *    public function __construct() {
 *        ...
 *        $this->stats = Stats::createForGame($this);
 *        ...
 *    }
 *
 * Initialize stats in setupNewGame():
 *
 *    protected function setupNewGame($players, $options = []) {
 *        ...
 *        // Initialize all stats to "zero" values
 *        $this->stats->initAll();
 *
 *        // Or, initialize each stat individually:
 *        $this->stats->PLAYER_MY_FLOAT_STAT->initAll(1.732);
 *        $this->stats->PLAYER_MY_BOOL_STAT->initAll(true);
 *
 *
 *        // Table stats are simpler, only one possible init:
 *        $this->stats->TABLE_MY_FLOAT_STAT->init(3.14159);
 *        ...
 *     }
 *
 * Updating / accessing stats (anywhere):
 *
 *    ...
 *    $stats->PLAYER_NUMBER_TURNS->inc($player_id);
 *    $stats->PLAYER_POINTS_FROM_ADJACENCY->inc($player_id, 5);
 *    $stats->TABLE_GAME_ENDED_DUE_TO_PIECE_EXHAUSTION->set(true);
 *    $stats->TABLE_OTHER_FLOAT->set(1.15 + $stats->TABLE_OTHER_FLOAT->get());
 *    ...
 */
declare(strict_types=1);

if (count($argv) > 2) {
    error_log("Usage: php gentstats.php [gamename]");
    exit(1);
}
// in PHP 8.5+, we can just use `last` instead of `array_key_last(array_flip())`
$gamename = $argv[1]
    ?? array_key_last(array_flip(explode(DIRECTORY_SEPARATOR, getcwd())));

function toIdentifier($name): string {
    return strtoupper(str_replace(" ","_",$name));
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
    /** @param int|float $delta */
    public function incStat(mixed $delta, string $name, ?int $player_id = null) : void;
    /** @param int|float|bool $val */
    public function setStat(mixed $val, string $name, ?int $player_id = null) : void;
    /** @return int|float|bool|null */
    public function getStat(string $name, ?int $player_id = null): mixed;
    /** @param int|float|bool $val */
    public function initTableStat(string $name, mixed $val): void;
    /** @param int|float|bool $val */
    public function initPlayerStat(string $name, mixed $val): void;

    public function enterDeferredMode(): void;
    /** @return list<StatOp> */
    public function exitDeferredMode(): array;
}


class GameStatsImpl implements StatsImpl {
    /** @var list<StatOp> */
    private array $operations = [];
    private bool $deferredMode = false;

    public function __construct(private \Bga\GameFramework\Table $game) {}

    #[\Override]
    public function enterDeferredMode(): void {
        $this->deferredMode = true;
    }

    #[\Override]
    public function exitDeferredMode(): array {
        $this->deferredMode = false;
        $result = $this->operations;
        $this->operations = [];
        return $result;
    }

    #[\Override]
    /** @param float|int $delta */
    public function incStat(mixed $delta, string $name, ?int $player_id = null) : void {
        if ($this->deferredMode) {
            $this->operations[] = new StatOp(OpType::INC, $name, $player_id, $delta);
        } else {
            if ($player_id !== null) {
                $this->game->playerStats->inc($name, $delta, $player_id);
            } else {
                $this->game->tableStats->inc($name, $delta);
            }
        }
    }

    #[\Override]
    /** @param float|int|bool $val */
    public function setStat(mixed $val, string $name, ?int $player_id = null) : void {
        if ($this->deferredMode) {
            $this->operations[] = new StatOp(OpType::SET, $name, $player_id, $val);
        } else {
            if ($player_id !== null) {
                $this->game->playerStats->set($name, $val, $player_id);
            } else {
                $this->game->tableStats->set($name, $val);
            }
        }
    }

    #[\Override]
    /** @return float|int|bool|null */
    public function getStat(string $name, ?int $player_id = null): mixed {
        /** @var float|int|bool */
        $val = ($player_id !== null)
            ? $this->game->playerStats->get($name, $player_id)
            : $this->game->tableStats->get($name);
        if ($this->deferredMode) {
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
        }
        return $val;
    }

    #[\Override]
    /** @param float|int|bool $val */
    public function initTableStat(string $name, mixed $val): void {
        $this->game->tableStats->init($name, $val);
    }
    /** @param int|float|bool $val */
    public function initPlayerStat(string $name, mixed $val): void {
        $this->game->playerStats->init($name, $val);
    }
}

enum OpType: string
{
    case SET = 'SET';
    case INC = 'INC';
}

class StatOp {
    /** @param int|float|bool|null $value */
    public function __construct(public readonly OpType $op_type, public readonly string $name, public readonly ?int $player_id, public readonly mixed $value) {}
}

class TestStatsImpl implements StatsImpl {
    /** @var array<string,int|float|bool> */
    private $tvals = [];
    /** @var array<string,array<int,int|float|bool>> */
    private $pvals = [];
    /** @var array<string,int|float|bool> */
    private $pinitvals = [];

    #[\Override]
    public function enterDeferredMode(): void { }

    #[\Override]
    /** @return array<int, StatOp> */
    public function exitDeferredMode(): array { return []; }

    #[\Override]
    /** @param int|float|bool $val */
    public function initPlayerStat(string $name, mixed $val): void {
        $this->pinitvals[$name] = $val;
    }

    #[\Override]
    /** @param int|float|bool $val */
    public function initTableStat(string $name, mixed $val): void {
        $this->tvals[$name] = $val;
    }

    #[\Override]
    /** @param int|float $delta */
    public function incStat(mixed $delta, string $name, ?int $player_id = null): void {
        if ($player_id === null) {
            $this->tvals[$name] += $delta;
        } else {
            if (!isset($this->pvals[$name])) {
                $this->pvals[$name] = [];
            }
            if (!isset($this->pvals[$name][$player_id])) {
                $this->pvals[$name][$player_id] = $this->pinitvals[$name];
            }
            $this->pvals[$name][$player_id] += $delta;
        }
    }

    #[\Override]
    /** @param int|float|bool $val */
    public function setStat(mixed $val, string $name, ?int $player_id = null): void {
        if ($player_id === null) {
            $this->tvals[$name] = $val;
        }
        else {
            if (!isset($this->pvals[$name])) {
                $this->pvals[$name] = [];
            }
            $this->pvals[$name][$player_id] = $val;
        }
    }

    #[\Override]
    /** @return int|bool|float|null */
    public function getStat(string $name, ?int $player_id = null): mixed {
        if ($player_id === null) {
            return $this->tvals[$name];
        }
        else {
            $key = $name . ':' . $player_id;
            if (isset($this->pvals[$name]) && isset($this->pvals[$name][$player_id])) {
                return $this->pvals[$name][$player_id];
            } else {
                return $this->pinitvals[$name];
            }
        }
    }
}


//
// Specific Stat types
//

class IntPlayerStat {
    /** @param StatsImpl $impl */
    function __construct(private mixed $impl, public readonly string $name) {
    }

    public function init(int $val = 0): void {
        $this->impl->initPlayerStat($this->name, $val);
    }

    public function inc(int $player_id, int $delta = 1): void {
        $this->impl->incStat($delta, $this->name, $player_id);
    }

    public function set(int $player_id, int $val): void {
        $this->impl->setStat($val, $this->name, $player_id);
    }

    public function get(int $player_id): int {
        return intval($this->impl->getStat($this->name, $player_id));
    }
}

class BoolPlayerStat {
    /** @param StatsImpl $impl */
    function __construct(private mixed $impl, public readonly string $name) {
    }

    public function init(bool $val = false): void {
        $this->impl->initPlayerStat($this->name, $val);
    }

    public function set(int $player_id, bool $val): void {
        $this->impl->setStat($val, $this->name, $player_id);
    }

    public function get(int $player_id): bool {
        return boolval($this->impl->getStat($this->name, $player_id));
    }
}

class FloatPlayerStat {
    /** @param StatsImpl $impl */
    function __construct(private mixed $impl, public readonly string $name) {
    }

    public function init(float $val = 0.0): void {
        $this->impl->initPlayerStat($this->name, $val);
    }

    public function add(int $player_id, float $delta): void {
        $this->impl->incStat($delta, $this->name, $player_id);
    }

    public function set(int $player_id, float $val): void {
        $this->impl->setStat($val, $this->name, $player_id);
    }

    public function get(int $player_id): float {
        return floatval($this->impl->getStat($this->name, $player_id));
    }
}

class IntTableStat {
    /** @param StatsImpl $impl */
    function __construct(private mixed $impl, public readonly string $name) {
    }

    public function init(int $val = 0): void {
        $this->impl->initTableStat($this->name, $val);
    }

    public function inc(int $delta = 1): void {
        $this->impl->incStat($delta, $this->name);
    }

    public function set(int $val): void {
        $this->impl->setStat($val, $this->name);
    }

    public function get(): int {
        return intval($this->impl->getStat($this->name));
    }
}

class BoolTableStat {
    /** @param StatsImpl $impl */
    function __construct(private mixed $impl, public readonly string $name) {
    }

    public function init(bool $val = false): void {
        $this->impl->initTableStat($this->name, $val);
    }

    public function set(bool $val): void {
        $this->impl->setStat($val, $this->name);
    }

    public function get(): bool {
        return boolval($this->impl->getStat($this->name));
    }
}

class FloatTableStat {
    /** @param StatsImpl $impl */
    function __construct(private mixed $impl, public readonly string $name) {
    }

    public function init(float $val = 0.0): void {
        $this->impl->initTableStat($this->name, $val);
    }

    public function add(float $delta): void {
        $this->impl->incStat($delta, $this->name);
    }

    public function set(float $val): void {
        $this->impl->setStat($val, $this->name);
    }

    public function get(): float {
        return floatval($this->impl->getStat($this->name));
    }
}

//
// The Stats class
//

class Stats {

    /**
     * Convenience factory method that also initializes.
     */
    public static function createForTest(): Stats {
        $stats = new Stats(new TestStatsImpl());
        $stats->initAll();
        return $stats;
    }

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

    public function initAll(): void {
<?php foreach (["player", "table"] as $scope) {
          foreach (["int", "float", "bool"] as $type) {
              foreach (statsFor($scope, $type) as $n => $id) {
                  $typename =  ucfirst($type) . ucfirst($scope);
                  $name = strtoupper($scope) . "_" . $id; ?>
      $this-><?php echo $name ?>->init();
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

    public function enterDeferredMode(): void {
        $this->impl->enterDeferredMode();
    }

    /** @return array<int, StatOp> */
    public function exitDeferredMode(): array {
        return $this->impl->exitDeferredMode();
    }

    /** @param array<int, StatOp> $statOps */
    public function applyAll(array $statOps): void {
        foreach ($statOps as $op) {
            switch ($op->op_type) {
                case OpType::INC:
                    if ($op->value !== null) {
                        $this->impl->incStat($op->value, $op->name, $op->player_id);
                    }
                    break;
                case OpType::SET:
                    if ($op->value !== null) {
                        $this->impl->setStat($op->value, $op->name, $op->player_id);
                    }
                    break;
            }
        }
    }

}
