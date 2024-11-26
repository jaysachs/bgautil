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

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Bga\Games\testgame\Stats;

class FakeImpl {
    private $vals = [];

    public function initStat($cat, $name, $value, $player_id = null) {
        if ($player_id === null) {
            if ($cat != "table") {
                throw new \InvalidArgumentException(
                    "table stats require null player_id");
            }
            $this->vals[$name] = $value;
        } else {
            if ($cat != "player") {
                throw new \InvalidArgumentException(
                    "player stats require player_id");
            }
            if (! isset($this->vals[$name])) {
                $this->vals[$name] = [];
            }
            $this->vals[$name][$player_id] = $value;
        }
    }

    public function getStat($name, $player_id = null) {
        if ($player_id === null) {
            return $this->vals[$name];
        }
        return $this->vals[$name][$player_id];
    }

    public function incStat($delta, $name, $player_id = null) {
        if ($player_id === null) {
            $this->vals[$name] += $delta;
        } else {
            $this->vals[$name][$player_id] += $delta;
        }
    }

    public function setStat($value, $name, $player_id = null) {
        if ($player_id === null) {
            $this->vals[$name] = $value;
        } else {
            $this->vals[$name][$player_id] = $value;
        }
    }
}

final class StatsTest extends TestCase
{
    const array PLAYER_IDS = [ 5, 7 ];
    private ?FakeImpl $impl;

    protected function setUp(): void {
        $this->impl = new FakeImpl();
        Stats::init($this->impl);
    }

    public function testInitAll(): void {
        Stats::initAll(self::PLAYER_IDS);

        $this->assertEquals(0, Stats::PLAYER_NUMBER_TURNS->get(5));
        $this->assertEquals(0, Stats::PLAYER_NUMBER_TURNS->get(7));
        $this->assertEquals(false, Stats::PLAYER_MET_OBJECTIVE->get(5));
        $this->assertEquals(false, Stats::PLAYER_MET_OBJECTIVE->get(7));
        $this->assertEquals(0.0, Stats::PLAYER_AVERAGE_DIE_ROLL->get(5));
        $this->assertEquals(0.0, Stats::PLAYER_AVERAGE_DIE_ROLL->get(7));

        $this->assertEquals(0, Stats::TABLE_CITIES_CAPTURED->get());
        $this->assertEquals(false, Stats::TABLE_SUDDEN_DEATH->get());
        $this->assertEquals(0.0, Stats::TABLE_AVERAGE_PIECES_PER_TURN->get());
    }

    public function testInitMap(): void {
        Stats::PLAYER_NUMBER_TURNS->initMap(self::PLAYER_IDS,
                                            function (int $p) { return $p*$p+1; });
        $this->assertEquals(26, Stats::PLAYER_NUMBER_TURNS->get(5));
        $this->assertEquals(50, Stats::PLAYER_NUMBER_TURNS->get(7));
    }

    public function testPlayerInt(): void {
        Stats::initAll(self::PLAYER_IDS);

        Stats::PLAYER_NUMBER_TURNS->set(5, 4);

        $this->assertEquals(4, Stats::PLAYER_NUMBER_TURNS->get(5));
        $this->assertEquals(0, Stats::PLAYER_NUMBER_TURNS->get(7));

        Stats::PLAYER_NUMBER_TURNS->inc(7);
        Stats::PLAYER_NUMBER_TURNS->inc(7);
        Stats::PLAYER_NUMBER_TURNS->inc(7, 3);

        $this->assertEquals(4, Stats::PLAYER_NUMBER_TURNS->get(5));
        $this->assertEquals(5, Stats::PLAYER_NUMBER_TURNS->get(7));
    }

    public function testPlayerBool(): void {
        Stats::initAll(self::PLAYER_IDS);

        Stats::PLAYER_MET_OBJECTIVE->set(5, true);

        $this->assertEquals(true, Stats::PLAYER_MET_OBJECTIVE->get(5));
        $this->assertEquals(false, Stats::PLAYER_MET_OBJECTIVE->get(7));
    }

    public function testPlayerFloat(): void {
        Stats::initAll(self::PLAYER_IDS);

        Stats::PLAYER_AVERAGE_DIE_ROLL->set(5, 1.732);

        $this->assertEquals(1.732, Stats::PLAYER_AVERAGE_DIE_ROLL->get(5));
        $this->assertEquals(0.0, Stats::PLAYER_AVERAGE_DIE_ROLL->get(7));

        Stats::PLAYER_AVERAGE_DIE_ROLL->add(7, 3.0);
        Stats::PLAYER_AVERAGE_DIE_ROLL->add(7, 3.14159);

        $this->assertEquals(1.732, Stats::PLAYER_AVERAGE_DIE_ROLL->get(5));
        $this->assertEquals(6.14159, Stats::PLAYER_AVERAGE_DIE_ROLL->get(7));
    }

    public function testTableInt(): void {
        Stats::initAll(self::PLAYER_IDS);

        Stats::TABLE_CITIES_CAPTURED->set(3);

        $this->assertEquals(3, Stats::TABLE_CITIES_CAPTURED->get(5));

        Stats::TABLE_CITIES_CAPTURED->inc();
        Stats::TABLE_CITIES_CAPTURED->inc();
        Stats::TABLE_CITIES_CAPTURED->inc(3);
        $this->assertEquals(8, Stats::TABLE_CITIES_CAPTURED->get(5));
    }

    public function testTableBool(): void {
        Stats::initAll(self::PLAYER_IDS);

        Stats::TABLE_SUDDEN_DEATH->set(true);

        $this->assertEquals(true, Stats::TABLE_SUDDEN_DEATH->get());
    }

    public function testTableFloat(): void {
        Stats::initAll(self::PLAYER_IDS);

        Stats::TABLE_AVERAGE_PIECES_PER_TURN->set(2.1);

        $this->assertEquals(2.1, Stats::TABLE_AVERAGE_PIECES_PER_TURN->get());

        Stats::TABLE_AVERAGE_PIECES_PER_TURN->add(3.0);
        Stats::TABLE_AVERAGE_PIECES_PER_TURN->add(5.7);

        $this->assertEquals(10.8, Stats::TABLE_AVERAGE_PIECES_PER_TURN->get());
    }
}
