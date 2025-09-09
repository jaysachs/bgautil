<?php

/**
 * gvstates -- generate DOT file for a BGA game state machine
 *
 * Usage:
 *
 *   php gvstates.php /path/to/states.inc.php
 *
 * emits the generated dotfile to stdout. You can omit the states file
 * and it will look in the current directory for `states.inc.php`
 *
 * A fuller example including conversion to image:
 *
 *   php gvstates.php states.inc.php | dot -T png > /tmp/mygamestates.png
 *
 * For documentation on Graphviz and DOT, see https://graphviz.org/
 */

namespace Bga\GameFramework {

enum StateType: string {
    case MULTIPLE_ACTIVE_PLAYER = "multipleactiveplayer";
    case PRIVATE = "private";
    case ACTIVE_PLAYER = "activeplayer";
    case GAME = "game";
    case SYSTEM = "system";
}

class GameState {
    public function __construct() { }

    public string $name;
    public StateType $type;
    public ?string $description = null;
    public ?string $descriptionmyturn = null;
    public string $action = "";
    public string $args = "";
    /** @var array<string,int> */
    public array $transitions = [];
    /** @var array<string> */
    public array $possibleactions = [];
    public bool $updateGameProgression = false;
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

} // namespace

namespace {

use Bga\GameFramework\GameState;
use Bga\GameFramework\GameStateBuilder;
use Bga\GameFramework\StateType;

function node(int $id,GameState $state): string {
    if ($id == 1) {
        return "";
    }
    if ($id == 99) {
        return "";
    }
    $shape = match ($state->type) {
        StateType::ACTIVE_PLAYER => 'parallelogram',
        StateType::MULTIPLE_ACTIVE_PLAYER => 'doublebox',
        StateType::PRIVATE => 'trapezium',
        StateType::GAME => 'box',
        StateType::SYSTEM => ($state->name == 'START' ? 'invtriangle' : 'octagon'),
    };
    $label = sprintf("<table border=\"0\"><tr><td colspan=\"2\"><b>%s</b></td></tr>", $state->name);
    if ($state->args != "") {
        $label .= sprintf("<tr><td><i>args</i></td><td>%s</td></tr>", $state->args);
    }
    if ($state->action != "") {
        $label .= sprintf("<tr><td><i>act</i></td><td>%s</td></tr>", $state->action);
    }
    $label .= "</table>";
    return sprintf("%s [fontname=Arial,shape=%s,label=<%s>];\n", $state->name, $shape, $label);
}

function edge(GameState $state, string $label, GameState $dest): string {
    return sprintf("%s -> %s [fontname=Arial,xlabel=\"%s\"];\n", $state->name, $dest->name, $label);
}

/** @param GameState[] $states */
function generateGraphViz(array $states): void { ?>
digraph {
    rankdir="TB"
    subgraph {
        rank=source
        START [fontname=Arial,shape=invtriangle]
    }
    subgraph {
        rank=sink
        END [fontname=Arial,shape=octagon]
    }
<?php
    $states[1] = GameStateBuilder::create()->name('START')->transitions(["" => 2])->type(StateType::SYSTEM,)->build();
    $states[99] = GameStateBuilder::create()->name('END')->transitions([])->type(StateType::SYSTEM,)->build();

    foreach ($states as $id => $state) {
        echo node($id, $state);
        foreach ($state->transitions as $label => $destid) {
            echo edge($state, $label, $states[$destid]);
        }
    }

?>
}
<?php
}


function clienttranslate(string $s): string { return $s; }

$file = "states.inc.php";
if (count($argv) == 2) {
    $file = $argv[1];
} else if (count($argv) > 2) {
    fwrite(STDERR, "Usage: php $argv[0] [ statesfilename ]\n");
    exit(1);
}
if (!include($file)) {
    fwrite(STDERR, "Unable to read $file\n");
    exit(1);
}

generateGraphViz($machinestates);

} // namespace
?>
