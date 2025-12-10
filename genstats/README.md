# genstats

**genstats** provides a tool for generating well-typed enum codes
corresponding to the BGA `stats.json` file.

## Generation

Generate the Stats class via

```sh
$ php genstats.php gamename > modules/php/Stats.php
```

(assumes the current directory is the main game directory where
`stats.json` is located).

The generated `Stats.php` file wil contain definitions of backed enums
for each statistic defined in `stats.json`, with the enum name derived
from the `stats.json` identifiers, and with typed methods for the
different kinds of stats (table / player, int / float / bool).

## Usage in game code

### Configuration

Insert one line of configuration into the `Game.php` constructor:

```php

   private Stats $stats;

   public function __construct() {
       ...
       $this->stats = Stats::createForGame($this);
       ...
   }
```

### Initialization

Initialize all stats in the `setupNewGame()`:

```php
    protected function setupNewGame($players, $options = []) {
       ...
       // Initialize all stats to "zero" values
       $this->stats->initAll(array_keys($players));
       ...
    }
```

Other options:
``` php
       ...
       // Initialize each stat individually:
       $this->stats->PLAYER_MY_FLOAT_STAT->init(array_keys($players), 1.732);
       Sthis->stats->PLAYER_MY_BOOL_STAT->init(array_keys($players), true);

       // Or, different value per player id:
       $this->stats->PLAYER_MY_OTHER_INT_STAT->initMap($array_keys($players),
           function ($pid) { return rand(0, 4); });

       // Table stats are simpler, only one possible init:
       Stats::TABLE_MY_FLOAT_STAT->init(3.14159);
       ...
    }
```

### Updating / accessing stats (anywhere):

```php
   ...
   $stats->PLAYER_NUMBER_TURNS->inc($player_id);
   $stats->PLAYER_POINTS_FROM_ADJACENCY->inc($player_id, 5);
   $stats->TABLE_GAME_ENDED_DUE_TO_PIECE_EXHAUSTION->set(true);
   $stats->TABLE_OTHER_FLOAT->set(1.15 + $stats->TABLE_OTHER_FLOAT->get());
   ...
```

### deferredMode

Supporting granular undo in a game can present difficulty for
statistics, since you should undo any statistic changes
made. Sometimes this is straightforward. Consider, however a passive
ability that is used implicitly in permitting a move, where you have a
statistic (per player) that tracks how often that passive ability
actually tooko effect. Undoing the move itself is straightforward, but
in order to undo the statistic, you also need to record in the
database what statistic changes were made.

To address this, `Stats` has a "deferredMode", during which statistic
changes are not recorded in the database but instead accumulated
in-memory. When deferred mode is explicitly exited, than array of
`StatOp` objects is returned. Those can be stored in the database
along with "turn progress"; on undo, just un-apply the turn progress
(as normal) and "throw away" the StatOps. When the turn is "committed"
(i.e. undo is no longer possible), you can apply the accumulated
StatOps via `$this->stats->applyAll(array $ops)`.

This approach is taken, rather than trying to "undo" the intermiate
statistics, because it's more complex to undo `set` of statistic
values; the current (previous) value would need to be recorded. While this
would be possible, it's more complex, and there's also a philosophical
justification in that the stats aren't "real" until the move is
committed. (Then again, stats probably aren't generally interesting
until after a game is over.) It would be reasonable to consider an
alternate "deferred mode" where stat applications are applied to the
DB, but also recorded in memory, and then on undo, an
`undoApplyAll(array)` function is called.

#### Example of deferredMode use:

``` php
  function actDoSomethingUndoable(...) {
    $this->stats->enterDeferredMode();
    ...
    // note we're passing the StatOp[] and expect it to be saved
    $this->persistUndableToDb(..., $this->stats->leaveDeferredMode());
    ...
  }

  function actUndo(...) {
    ...
    $move = $this->retrieveUndoableMove();
    ...
    $this->persistUndoneState($move);
  }

  function actTurnDone(...) {
    ...
    $deferredStats = $this->retrieveDeferredStats();
    $this->stats->applyAll($deferredStats);
    ...
  }
```

## TODOs:

 * possibly improve `toIdentifier()`.
