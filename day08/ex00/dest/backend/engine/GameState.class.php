<?php

require_once __DIR__ . '/../interfaces/DBStorable.interface.php';
require_once __DIR__ . '/../ships/AShip.class.php';

class GameState implements ITurnBased, JsonSerializable
{

  const PHASE_SELECT_PLAYER = 1;
  const PHASE_SELECT_SHIP   = 2;
  const PHASE_USE_SHIP      = 3;
  const PHASE_RESET         = 4;

  private int   $_phase = GameState::PHASE_SELECT_PLAYER;

  private array $_players         = [];
  private int   $_currentPlayerID = 0;
  private int   $_currentShipID   = 0;

  private string $_status = '';

  public static
  function recreate(
    $data
  ) {
    $ret = new GameState();

    $ret -> _phase           = $data['phase'];
    $ret -> _currentPlayerID = $data['currentPlayerID'];
    $ret -> _currentShipID   = $data['currentShipID'];
    $ret -> _status          = $data['status'];

    foreach ($data['players'] as $playerData) {
      foreach ($playerData['ships'] as $shipData) {
        // well
        // we have to recreate object of exact class
        // because we can't instantiate abstract AShip
        // so we have to store ship type
        // and it probably requires reflection
        // so...
        // FIXME it's broken Kevin
      }
    }

    return $ret;
  }

  public
  function addPlayer(
    bool $setCurrent = false
  ): int {
    $id                    = count($this -> _players) + 1;
    $this -> _players[$id] = [
      'id'    => $id,
      'ships' => [],
    ];

    if ($setCurrent) {
      $this -> _currentPlayerID = $id;
    }

    return $id;
  }

  public
  function getCurrentPlayerID(): int
  {
    return $this -> _currentPlayerID;
  }

  public
  function nextPlayer(): GameState
  {
    foreach ($this -> _players as $player) {
      if ($player['id'] === $this -> _currentPlayerID) {
        continue;
      }

      $this -> _currentPlayerID = $player['id'];
      return $this;
    }

    return $this;
  }

  public
  function getPhase(): int
  {
    return $this -> _phase;
  }

  public
  function setPhase(
    int $value
  ): GameState {
    $this -> _phase = $value;

    switch ($this -> _phase) {
      case GameState::PHASE_SELECT_SHIP:
        $this -> _status = sprintf(
          'Игрок #%d выберите свой корабль.',
          $this -> _currentPlayerID
        );
        break;
    }

    return $this;
  }

  public
  function getShipAtLocation(
    int $x,
    int $y
  ): ?AShip {
    foreach ($this -> _players as $player) {
      foreach ($player['ships'] as $ship) {
        /** @var AShip $ship */
        if (!$ship -> isOnXY($x, $y)) {
          continue;
        }

        return $ship;
      }
    }

    return null;
  }

  public
  function addShip(
    AShip $ship
  ): GameState {
    $this -> _players[$this -> _currentPlayerID]['ships'][] = $ship;
    return $this;
  }

  public
  function getStatus(): string
  {
    return $this -> _status;
  }

  public
  function setStatus(
    string $value
  ): GameState {
    $this -> _status = $value;
    return $this;
  }

  public
  function setCurrentShipID(
    int $id
  ): bool {
    foreach ($this -> _players as $player) {
      foreach ($player['ships'] as $ship) {
        /** @var AShip $ship */
        if ($ship -> getID() === $id) {
          if ($ship -> getPlayerID() !== $this -> _currentPlayerID) {
            $this -> _status = 'Вы не можете выбрать чужой корабль.';
            return false;
          }

          $this -> _currentShipID = $id;
          $this -> _status        = sprintf(
            'Вы выбрали корабль %s #%d.',
            $ship -> getName(),
            $ship -> getID()
          );
          return true;
        }
      }
    }

    $this -> _status = 'Такого корабля нет в игре.';
    return false;
  }

  public
  function getCurrentShip(): ?AShip
  {
    foreach ($this -> _players as $player) {
      foreach ($player['ships'] as $ship) {
        /** @var AShip $ship */
        if ($ship -> getID() === $this -> _currentShipID) {
          return $ship;
        }
      }
    }

    return null;
  }

  public
  function reset(): void
  {
    foreach ($this -> _players as $player) {
      foreach ($player['ships'] as $ship) {
        /** @var AShip $ship */
        $ship -> reset();
      }
    }
  }

  public
  function jsonSerialize()
  {
    $ret = [
      'phase'           => $this -> _phase,
      'players'         => [],
      'currentPlayerID' => $this -> _currentPlayerID,
      'currentShipID'   => $this -> _currentShipID,
      'status'          => $this -> _status,
    ];
    foreach ($this -> _players as $player) {
      $retP = [
        'id'    => $player['id'],
        'ships' => [],
      ];
      foreach ($player['ships'] as $ship) {
        /** @var AShip $ship */
        $retP['ships'][] = $ship -> jsonSerialize();
      }
      $ret['players'][] = $retP;
    }
    return $ret;
  }

}
