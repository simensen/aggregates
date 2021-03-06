<?php

namespace EventCentric\EventStore;

use EventCentric\Contracts\Contract;
use EventCentric\EventStore\CommitId;
use EventCentric\EventStore\EventEnvelope;
use EventCentric\Identity\Identity;
use EventCentric\Persistence\Persistence;

final class EventStream
{
    /**
     * @var Contract
     */
    private $streamContract;

    /**
     * @var Identity
     */
    private $streamId;

    private $committedEventEnvelopes = [];

    private $pendingEventEnvelopes = [];

    /**
     * @var Persistence
     */
    private $persistence;

    private function __construct(Persistence $persistence, Contract $streamContract, Identity $streamId)
    {
        $this->streamContract = $streamContract;
        $this->streamId = $streamId;
        $this->persistence = $persistence;
    }

    /**
     * @param Persistence $persistence
     * @param Contract $streamContract
     * @param Identity $streamId
     * @return EventStream
     */
    public static function create(Persistence $persistence, Contract $streamContract, Identity $streamId)
    {
        $eventStream = new EventStream($persistence, $streamContract, $streamId);
        return $eventStream;
    }

    /**
     * @param Persistence $persistence
     * @param Contract $streamContract
     * @param Identity $streamId
     * @return EventStream
     */
    public static function open(Persistence $persistence, Contract $streamContract, Identity $streamId)
    {
        $eventStream = new EventStream($persistence, $streamContract, $streamId);
        $eventStream->committedEventEnvelopes = $persistence->fetch($streamContract, $streamId);
        return $eventStream;
    }

    /**
     * @param EventEnvelope $eventEnvelope
     * @return void
     */
    public function append(EventEnvelope $eventEnvelope)
    {
        $this->pendingEventEnvelopes[] = $eventEnvelope;
    }

    /**
     * @param EventEnvelope[] $envelopes
     * @return void
     */
    public function appendAll(array $envelopes)
    {
        foreach($envelopes as $envelope) {
            $this->append($envelope);
        }
    }

    /**
     * @return EventEnvelope[]
     */
    public function all()
    {
        return array_merge($this->committedEventEnvelopes, $this->pendingEventEnvelopes);
    }

    public function commit(CommitId $commitId)
    {
        $this->persistence->commit(
            $commitId,
            $this->streamContract,
            $this->streamId,
            count($this->committedEventEnvelopes),
            $this->pendingEventEnvelopes
        );

        $this->committedEventEnvelopes = array_merge($this->committedEventEnvelopes, $this->pendingEventEnvelopes);
        $this->pendingEventEnvelopes = [];
    }


} 