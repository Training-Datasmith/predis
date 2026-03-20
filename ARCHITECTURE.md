# Predis — Architecture

## Purpose

Predis is a pure-PHP Redis/Valkey client. It requires no C extensions (though it optionally uses the `relay` extension for performance), communicates over TCP or Unix sockets using the Redis Serialization Protocol (RESP), and supports single-node, replication, and cluster topologies.

## Directory Structure

```
src/
  Client.php                    — Public entry point; executes Redis commands
  Client_Interface.php          — Public API contract
  Client_Configuration.php      — Typed configuration value object
  Client_Exception.php          — Base exception for client-level errors

  Command/
    Command_Interface.php       — Contract for a single Redis command
    Command.php                 — Base implementation (id, arguments, response parsing)
    Factory.php                 — Creates Command instances by command ID string
    Prefixable_Command.php      — Commands that support key prefixing
    Argument/                   — Typed argument builders (Search, TimeSeries, Geo, …)
    Container/                  — Sub-command dispatchers (ACL, CLIENT, CLUSTER, …)
    Processor/
      Key_Prefix_Processor.php  — Prepends namespace prefix to all command keys

  Connection/
    Connection_Interface.php    — Low-level read/write over a socket
    Parameters.php              — Connection URI/array → typed connection parameters
    Stream_Connection.php       — TCP / Unix socket transport
    Relay_Connection.php        — relay extension transport (in-memory cache)
    Aggregate_Connection_Interface.php — Multiple-node connection base
    Cluster/                    — Redis Cluster connection and slot routing
    Replication/                — Master/replica read-preference routing

  Configuration/
    Options.php                 — Top-level client options container
    Options_Interface.php       — Typed accessors for all options

  Pipeline/
    Pipeline.php                — Buffers commands and flushes in one round-trip
    Atomic.php                  — Pipeline wrapped in MULTI/EXEC transaction
    Fire_And_Forget.php         — Pipeline that ignores responses

  Transaction/
    Multi_Exec.php              — CAS (WATCH/MULTI/EXEC) transaction support

  Consumer/
    Pub_Sub/Consumer.php        — Blocking pub/sub message loop
    Push/Consumer.php           — Push notifications (RESP3)
    Monitor/Consumer.php        — Redis MONITOR command consumer

  Cluster/
    Slot_Map.php                — Cluster node ↔ hash-slot mapping
    Redis_Strategy.php          — Hash-slot computation (CRC16 of key)
    Distributor/                — Consistent-hashing for manual cluster mode

  Response/
    Server_Exception.php        — Redis error response (-ERR …)
    Response_Interface.php      — Marker for parsed server responses
```

## Key Design Decisions

### Command Objects
Every Redis command is a first-class object implementing `Command_Interface`. This allows command-specific argument validation, key extraction for routing, and response parsing to live together.

### Factory Pattern for Commands
`Command\Factory` maps command-ID strings to class names. Custom commands can be registered via `Factory::define()`. The client is not hard-coded to any command set.

### Pipeline Batching
`Client::pipeline()` returns a `Pipeline` context object. Commands dispatched to it are buffered then flushed as a single write, minimising round-trips. `Atomic` wraps the pipeline in MULTI/EXEC.

### Aggregate Connections
Cluster and replication are implemented as `Aggregate_Connection_Interface` instances that contain multiple `Connection_Interface` nodes and route commands to the correct node transparently.

### Key Prefixing
`Key_Prefix_Processor` prepends a namespace to all command keys. It is registered once at the options level and applied automatically via the command processor chain.

## Extension Points

- **Custom commands**: implement `Command_Interface`, register via `Factory::define()`
- **Custom connection**: implement `Connection_Interface`
- **Custom options**: extend `Options` and override option definitions
- **Middleware/Processors**: implement a callable `(CommandInterface): CommandInterface`

## Dependency Flow

```
Client::set('key', 'value')
  → __call dispatches to Command\Factory
  → Command object created (SET)
  → Key_Prefix_Processor applied
  → Connection::execute_command(Command)
       → RESP serialization → socket write
       → socket read → response parsing
  → parsed value returned
```
