<?php

namespace App\Services;

use App\Models\Command;
use Telegram\Bot\Objects\ResponseObject;
use Telegram\Bot\Commands\Parser;
use Telegram\Bot\Commands\Entity;
use InvalidArgumentException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Helpers\Validator;

class Telegram
{
    /**
     * Handles Inbound Messages and Executes Appropriate Command.
     */
    public static function getCommand(ResponseObject $update): ?Command
    {
        if (!Validator::hasCommand($update)) return null;
        $entity = Entity::from($update)->commandEntities()->first();
        return self::process($update, $entity);
    } 

    /**
     * Execute a bot command from the update text.
     *
     * @throws TelegramSDKException
     */
    public static function process(ResponseObject $update, array $entity): ?Command
    {
        $command = self::parseCommand(
            Entity::from($update)->text(),
            $entity['offset'],
            $entity['length']
        );
        return Command::whereIn('name', [$command, "/$command", "@$command"])->first();
    }

    /**
     * Parse a Command for a Match.
     */
    public static function parseCommand(string $text, int $offset, int $length): string
    {
        if (blank($text)) {
            throw new InvalidArgumentException('Message is empty, Cannot parse for command');
        }
        return Parser::between(mb_substr($text, $offset, $length, 'UTF-8'), '/', '@');
    }
}
