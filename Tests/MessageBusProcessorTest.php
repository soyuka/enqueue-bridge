<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sam\Symfony\Bridge\EnqueueMessage\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;
use Symfony\Component\Messenger\Asynchronous\Transport\ReceivedMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Sam\Symfony\Bridge\EnqueueMessage\MessageBusProcessor;
use Sam\Symfony\Bridge\EnqueueMessage\Exception\RejectMessageException;
use Sam\Symfony\Bridge\EnqueueMessage\Exception\RequeueMessageException;
use Prophecy\Argument;

class MessageBusProcessorTest extends TestCase
{
    private function getTestMessage()
    {
        $messageProphecy = $this->prophesize(PsrMessage::class);
        $messageProphecy->getBody()->shouldBeCalled()->willReturn('body');
        $messageProphecy->getHeaders()->shouldBeCalled()->willReturn(array('header'));
        $messageProphecy->getProperties()->shouldBeCalled()->willReturn('props');

        return $messageProphecy->reveal();
    }

    public function testProcess()
    {
        $message = $this->getTestMessage();
        $receivedMessage = new ReceivedMessage('test');
        $contextProphecy = $this->prophesize(PsrContext::class);
        $busProphecy = $this->prophesize(MessageBusInterface::class);
        $busProphecy->dispatch($receivedMessage)->shouldBeCalled();
        $decoderProphecy = $this->prophesize(DecoderInterface::class);
        $decoderProphecy->decode(array(
            'body' => 'body',
            'headers' => array('header'),
            'properties' => 'props',
        ))->shouldBeCalled()->willReturn($receivedMessage);

        $messageBusProcessor = new MessageBusProcessor($busProphecy->reveal(), $decoderProphecy->reveal());
        $this->assertSame(PsrProcessor::ACK, $messageBusProcessor->process($message, $contextProphecy->reveal()));
    }

    public function testProcessReject()
    {
        $message = $this->getTestMessage();
        $receivedMessage = new ReceivedMessage('test');
        $contextProphecy = $this->prophesize(PsrContext::class);
        $decoderProphecy = $this->prophesize(DecoderInterface::class);
        $decoderProphecy->decode(Argument::any())->shouldBeCalled()->willReturn($receivedMessage);
        $busProphecy = $this->prophesize(MessageBusInterface::class);
        $busProphecy->dispatch($receivedMessage)->shouldBeCalled()->willThrow(new RejectMessageException());

        $messageBusProcessor = new MessageBusProcessor($busProphecy->reveal(), $decoderProphecy->reveal());
        $this->assertSame(PsrProcessor::REJECT, $messageBusProcessor->process($message, $contextProphecy->reveal()));
    }

    public function testProcessRequeue()
    {
        $message = $this->getTestMessage();
        $receivedMessage = new ReceivedMessage('test');
        $contextProphecy = $this->prophesize(PsrContext::class);
        $decoderProphecy = $this->prophesize(DecoderInterface::class);
        $decoderProphecy->decode(Argument::any())->shouldBeCalled()->willReturn($receivedMessage);
        $busProphecy = $this->prophesize(MessageBusInterface::class);
        $busProphecy->dispatch($receivedMessage)->shouldBeCalled()->willThrow(new RequeueMessageException());

        $messageBusProcessor = new MessageBusProcessor($busProphecy->reveal(), $decoderProphecy->reveal());
        $this->assertSame(PsrProcessor::REQUEUE, $messageBusProcessor->process($message, $contextProphecy->reveal()));
    }

    public function testProcessRejectAnyException()
    {
        $message = $this->getTestMessage();
        $receivedMessage = new ReceivedMessage('test');
        $contextProphecy = $this->prophesize(PsrContext::class);
        $decoderProphecy = $this->prophesize(DecoderInterface::class);
        $decoderProphecy->decode(Argument::any())->shouldBeCalled()->willReturn($receivedMessage);
        $busProphecy = $this->prophesize(MessageBusInterface::class);
        $busProphecy->dispatch($receivedMessage)->shouldBeCalled()->willThrow(new \InvalidArgumentException());

        $messageBusProcessor = new MessageBusProcessor($busProphecy->reveal(), $decoderProphecy->reveal());
        $this->assertSame(PsrProcessor::REJECT, $messageBusProcessor->process($message, $contextProphecy->reveal()));
    }
}
