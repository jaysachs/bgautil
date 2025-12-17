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

namespace {

use PHPUnit\Framework\TestCase;

use Bga\Games\testgame\Stats;

final class StatsTest extends TestCase
{
    const array PLAYER_IDS = [ 5, 7 ];
    private Stats $stats;

    protected function setUp(): void {
        $this->stats = Stats::createForTest();
    }

    public function testInitAll(): void {
        $this->stats->initAll(self::PLAYER_IDS);

        $this->assertEquals(0, $this->stats->PLAYER_NUMBER_TURNS->get(5));
        $this->assertEquals(0, $this->stats->PLAYER_NUMBER_TURNS->get(7));
        $this->assertEquals(false, $this->stats->PLAYER_MET_OBJECTIVE->get(5));
        $this->assertEquals(false, $this->stats->PLAYER_MET_OBJECTIVE->get(7));
        $this->assertEquals(0.0, $this->stats->PLAYER_AVERAGE_DIE_ROLL->get(5));
        $this->assertEquals(0.0, $this->stats->PLAYER_AVERAGE_DIE_ROLL->get(7));

        $this->assertEquals(0, $this->stats->TABLE_CITIES_CAPTURED->get());
        $this->assertEquals(false, $this->stats->TABLE_SUDDEN_DEATH->get());
        $this->assertEquals(0.0, $this->stats->TABLE_AVERAGE_PIECES_PER_TURN->get());
    }

    public function testPlayerInt(): void {
        $this->stats->initAll(self::PLAYER_IDS);

        $this->stats->PLAYER_NUMBER_TURNS->set(5, 4);

        $this->assertEquals(4, $this->stats->PLAYER_NUMBER_TURNS->get(5));
        $this->assertEquals(0, $this->stats->PLAYER_NUMBER_TURNS->get(7));

        $this->stats->PLAYER_NUMBER_TURNS->inc(7);
        $this->stats->PLAYER_NUMBER_TURNS->inc(7);
        $this->stats->PLAYER_NUMBER_TURNS->inc(7, 3);

        $this->assertEquals(4, $this->stats->PLAYER_NUMBER_TURNS->get(5));
        $this->assertEquals(5, $this->stats->PLAYER_NUMBER_TURNS->get(7));
    }

    public function testPlayerBool(): void {
        $this->stats->initAll(self::PLAYER_IDS);

        $this->stats->PLAYER_MET_OBJECTIVE->set(5, true);

        $this->assertEquals(true, $this->stats->PLAYER_MET_OBJECTIVE->get(5));
        $this->assertEquals(false, $this->stats->PLAYER_MET_OBJECTIVE->get(7));
    }

    public function testPlayerFloat(): void {
        $this->stats->initAll(self::PLAYER_IDS);

        $this->stats->PLAYER_AVERAGE_DIE_ROLL->set(5, 1.732);

        $this->assertEquals(1.732, $this->stats->PLAYER_AVERAGE_DIE_ROLL->get(5));
        $this->assertEquals(0.0, $this->stats->PLAYER_AVERAGE_DIE_ROLL->get(7));

        $this->stats->PLAYER_AVERAGE_DIE_ROLL->add(7, 3.0);
        $this->stats->PLAYER_AVERAGE_DIE_ROLL->add(7, 3.14159);

        $this->assertEquals(1.732, $this->stats->PLAYER_AVERAGE_DIE_ROLL->get(5));
        $this->assertEquals(6.14159, $this->stats->PLAYER_AVERAGE_DIE_ROLL->get(7));
    }

    public function testTableInt(): void {
        $this->stats->initAll(self::PLAYER_IDS);

        $this->stats->TABLE_CITIES_CAPTURED->set(3);

        $this->assertEquals(3, $this->stats->TABLE_CITIES_CAPTURED->get(5));

        $this->stats->TABLE_CITIES_CAPTURED->inc();
        $this->stats->TABLE_CITIES_CAPTURED->inc();
        $this->stats->TABLE_CITIES_CAPTURED->inc(3);
        $this->assertEquals(8, $this->stats->TABLE_CITIES_CAPTURED->get(5));
    }

    public function testTableBool(): void {
        $this->stats->initAll(self::PLAYER_IDS);

        $this->stats->TABLE_SUDDEN_DEATH->set(true);

        $this->assertEquals(true, $this->stats->TABLE_SUDDEN_DEATH->get());
    }

    public function testTableFloat(): void {
        $this->stats->initAll(self::PLAYER_IDS);

        $this->stats->TABLE_AVERAGE_PIECES_PER_TURN->set(2.1);

        $this->assertEquals(2.1, $this->stats->TABLE_AVERAGE_PIECES_PER_TURN->get());

        $this->stats->TABLE_AVERAGE_PIECES_PER_TURN->add(3.0);
        $this->stats->TABLE_AVERAGE_PIECES_PER_TURN->add(5.7);

        $this->assertEquals(10.8, $this->stats->TABLE_AVERAGE_PIECES_PER_TURN->get());
    }
}

}
