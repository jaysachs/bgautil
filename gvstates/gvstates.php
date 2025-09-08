<?php

/**
 * gvstates -- generate DOT file for a BGA game state machine
 *
 * Usage:
 *   Run in same directory as your `states.inc.php` file.
 *
 *      php path/to/gvstates.php | dot -T png > /tmp/mygamestates.png
 *
 *   (or whatever output format you want & your dot command supports).
 */

namespace Bga\GameFramework {

enum StateType: string {
    case ACTIVE_PLAYER = "activeplayer";
    case GAME = "game";
    case SYSTEM = "system";
}

class GameState {
    public function __construct() { }

    public string $name;
    public StateType $type;
    public string $description;
    public string $descriptionmyturn;
    public string $action;
    public string $args;
    /** @var array<string,int> */
    public array $transitions;
    /** @var array<string> */
    public array $possibleactions;
    public bool $updateGameProgression;
}

class GameStateBuilder {


    private function __construct(private GameState $gamestate) {}

    public static function create(): GameStateBuilder {
        return new GameStateBuilder(new GameState());
    }

    public function name(string $val): GameStateBuilder {
        $this->gamestate->name = $val;
        return $this;
    }

    public function type(StateType $val): GameStateBuilder {
        $this->gamestate->type = $val;
        return $this;
    }

    public function description(string $val): GameStateBuilder {
        $this->gamestate->description = $val;
        return $this;
    }

    public function descriptionmyturn(string $val): GameStateBuilder {
        $this->gamestate->descriptionmyturn = $val;
        return $this;
    }

    public function action(string $val): GameStateBuilder {
        $this->gamestate->action = $val;
        return $this;
    }

    public function args(string $val): GameStateBuilder {
        $this->gamestate->args = $val;
        return $this;
    }

    public function updateGameProgression(bool $val): GameStateBuilder {
        $this->gamestate->updateGameProgression = $val;
        return $this;
    }

    public function transitions(array $val): GameStateBuilder {
        $this->gamestate->transitions = $val;
        return $this;
    }

    public function possibleactions(array $val): GameStateBuilder {
        $this->gamestate->possibleactions = $val;
        return $this;
    }

    public function build(): GameState {
        return $this->gamestate;
    }
}

}

namespace {

use Bga\GameFramework\GameState;
use Bga\GameFramework\GameStateBuilder;
use Bga\GameFramework\StateType;

function node(GameState $state): string {
    $shape = match ($state->type) {
        StateType::ACTIVE_PLAYER => 'box',
        StateType::GAME => 'ellipse',
        StateType::SYSTEM => 'doublecircle'
    };
    return sprintf("%s [shape=%s];\n", $state->name, $shape);
}

function edge(GameState $state, string $label, GameState $dest): string {
    return sprintf("%s -> %s [label=\"%s\"];\n", $state->name, $dest->name, $label);
}

/** @param GameState[] $states */
function generateGraphViz(array $states): void { ?>
digraph {
<?php
    $states[1] = GameStateBuilder::create()->name('START')->transitions(["" => 2])->type(StateType::SYSTEM,)->build();
    $states[99] = GameStateBuilder::create()->name('END')->transitions([])->type(StateType::SYSTEM,)->build();
    foreach ($states as $id => $state) {
        echo node($state);
        foreach ($state->transitions as $label => $destid) {
            echo edge($state, $label, $states[$destid]);
        }
    }

?>
}
<?php
}


function clienttranslate(string $s): string { return $s; }

require('states.inc.php');

generateGraphViz($machinestates);

}
?>
