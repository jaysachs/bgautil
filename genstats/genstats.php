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

    $s = $all_stats[$t_or_p];
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
declare(strict_types=1);

namespace Bga\Games\<?php echo $gamename ?>\StatsGen;

class Impl {
    static mixed $impl = null;
};

enum IntPlayerStats: string {
<?php foreach (statsFor("player", "int") as $name => $id) { ?>
    case <?php echo $id ?> = "<?php echo $name ?>";
<?php } ?>

    public function inc(int $player_id, int $delta = 1): void {
        Impl::$impl->incStat($delta, $this->value, $player_id);
    }

    public function set(int $player_id, int $val): void {
        Impl::$impl->setStat($val, $this->value, $player_id);
    }

    public function get(int $player_id): int {
        return Impl::$impl->getStat($this->value, $player_id);
    }

    public function init(int $player_id, int $val = 0): void {
        Impl::$impl->initStat("player", $this->value, $val, $player_id);
    }

    public function initAll(array /*int*/ $player_ids, int $val = 0): void {
        foreach ($player_ids as $pid) {
            $this->init($pid, $val);
        }
    }

    public function initMap(array /* int */ $player_ids, \Closure $val): void {
        foreach ($player_ids as $pid) {
            $this->init($pid, $val($pid));
        }
    }
}

enum FloatPlayerStats: string {
<?php foreach (statsFor("player", "float") as $name => $id) { ?>
    case <?php echo $id ?> = "<?php echo $name ?>";
<?php } ?>

    public function add(int $player_id, float $delta): void {
        Impl::$impl->incStat($delta, $this->value, $player_id);
    }

    public function set(int $player_id, float $val): void {
        Impl::$impl->setStat($val, $this->value, $player_id);
    }

    public function get(int $player_id): float {
        return Impl::$impl->getStat($this->value, $player_id);
    }

    public function init(int $player_id, float $val = 0.0): void {
        Impl::$impl->initStat("player", $this->value, $val, $player_id);
    }

    public function initAll(array /*int*/ $player_ids, float $val = 0.0): void {
        foreach ($player_ids as $pid) {
            $this->init($pid, $val);
        }
    }

    public function initMap(array /*int*/ $player_ids, \Closure $val): void {
        foreach ($player_ids as $pid) {
            $this->init($pid, $val($pid));
        }
    }
}

enum BoolPlayerStats: string {
<?php foreach (statsFor("player", "bool") as $name => $id) { ?>
    case <?php echo $id ?> = "<?php echo $name ?>";
<?php } ?>

    public function set(int $player_id, bool $val): void {
        Impl::$impl->setStat($val, $this->value, $player_id);
    }

    public function get(int $player_id): bool {
        return Impl::$impl->getStat($this->value, $player_id);
    }

    public function init(int $player_id, bool $val = false): void {
        Impl::$impl->initStat("player", $this->value, $val, $player_id);
    }

    public function initAll(array /*int*/ $player_ids, bool $val = false): void {
        foreach ($player_ids as $pid) {
            $this->init($pid, $val);
        }
    }

    public function initMap(array /*int*/ $player_ids, \Closure $val): void {
        foreach ($player_ids as $pid) {
            $this->init($pid, $val($pid));
        }
    }
}

enum IntTableStats: string {
<?php foreach (statsFor("table", "int") as $name => $id) { ?>
    case <?php echo $id ?> = "<?php echo $name ?>";
<?php } ?>

    public function inc(int $delta = 1): void {
        Impl::$impl->incStat($delta, $this->value);
    }

    public function set(int $val): void {
        Impl::$impl->setStat($val, $this->value);
    }

    public function get(): int {
        return Impl::$impl->getStat($this->value);
    }

    public function init(int $val = 0): void {
        Impl::$impl->initStat("table", $this->value, $val);
    }
}

enum FloatTableStats: string {
<?php foreach (statsFor("table", "float") as $name => $id) { ?>
    case <?php echo $id ?> = "<?php echo $name ?>";
<?php } ?>

    public function add(float $delta): void {
        Impl::$impl->incStat($delta, $this->value);
    }

    public function set(float $val): void {
        Impl::$impl->setStat($val, $this->value);
    }

    public function get(): float {
        return Impl::$impl->getStat($this->value);
    }

    public function init(float $val = 0.0): void {
        Impl::$impl->initStat("table", $this->value, $val);
    }
}

enum BoolTableStats: string {
<?php foreach (statsFor("table", "bool") as $name => $id) { ?>
    case <?php echo $id ?> = "<?php echo $name ?>";
<?php } ?>

    public function set(bool $val): void {
        Impl::$impl->setStat($val, $this->value);
    }

    public function get(): bool {
        return Impl::$impl->getStat($this->value);
    }

    public function init(bool $val = false): void {
        Impl::$impl->initStat("table", $this->value, $val);
    }
}

namespace Bga\Games\<?php echo $gamename ?>;
use Bga\Games\<?php echo $gamename ?>\StatsGen\ {
    IntPlayerStats,
    BoolPlayerStats,
    FloatPlayerStats,
    IntTableStats,
    BoolTableStats,
    FloatTableStats,
    Impl,
};

class Stats {
    // Player int stats
<?php foreach (statsFor("player", "int") as $n => $id) { ?>
    public const IntPlayerStats PLAYER_<?php echo $id ?> = IntPlayerStats::<?php echo $id ?>;
<?php } ?>

    // Player float stats
<?php foreach (statsFor("player", "float") as $n => $id) { ?>
    public const FloatPlayerStats PLAYER_<?php echo $id ?> = FloatPlayerStats::<?php echo $id ?>;
<?php } ?>

    // Player bool stats
<?php foreach (statsFor("player", "bool") as $n => $id) { ?>
    public const BoolPlayerStats  PLAYER_<?php echo $id ?> = BoolPlayerStats::<?php echo $id ?>;
<?php } ?>

    // Table int stats
<?php foreach (statsFor("table", "int") as $n => $id) { ?>
    public const IntTableStats TABLE_<?php echo $id ?> = IntTableStats::<?php echo $id ?>;
<?php } ?>

    // Table float stats
<?php foreach (statsFor("table", "float") as $n => $id) { ?>
    public const FloatTableStats TABLE_<?php echo $id ?> = FloatTableStats::<?php echo $id ?>;
<?php } ?>

    // Table bool stats
<?php foreach (statsFor("table", "bool") as $n => $id) { ?>
    public const BoolTableStats TABLE_<?php echo $id ?> = BoolTableStats::<?php echo $id ?>;
<?php } ?>

    public static function init(mixed $the_impl): void {
        Impl::$impl = $the_impl;
    }

    /*
     * Convenience method to initialize all stats to "zero".
     */
    public static function initAll(array /* int */ $player_ids): void {
<?php foreach (statsFor("player", "int") as $n => $id) { ?>
        self::PLAYER_<?php echo $id ?>->initAll($player_ids, 0);
<?php } ?>
<?php foreach (statsFor("player", "float") as $n => $id) { ?>
        self::PLAYER_<?php echo $id ?>->initAll($player_ids, 0.0);
<?php } ?>
<?php foreach (statsFor("player", "bool") as $n => $id) { ?>
        self::PLAYER_<?php echo $id ?>->initAll($player_ids, false);
<?php } ?>
<?php foreach (statsFor("table", "int") as $n => $id) { ?>
        self::TABLE_<?php echo $id ?>->init(0);
<?php } ?>
<?php foreach (statsFor("table", "float") as $n => $id) { ?>
        self::TABLE_<?php echo $id ?>->init(0.0);
<?php } ?>
<?php foreach (statsFor("table", "bool") as $n => $id) { ?>
        self::TABLE_<?php echo $id ?>->init(false);
<?php } ?>
    }
}
