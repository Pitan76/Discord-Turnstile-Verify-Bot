<?php
// https://discord.com/api/oauth2/authorize?client_id=1092461471818068059&permissions=8&scope=bot%20applications.commands
file_put_contents("time.txt", time());

include __DIR__.'/vendor/autoload.php';
use Discord\X;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\ActionRow;

$token = getenv("TOKEN");

$clientId = "";
$clientUser = "";

$client = new Discord([
  'token' => $token,
]);

$client->on('ready', function($discord) {
  echo "Bot is ready.", PHP_EOL;
  $clientUser = $discord->user;
  $clientId = $clientUser->id;

  $discord->application->commands->save(
    $discord->application->commands->create(CommandBuilder::new()
        ->setName('setup')
        ->setDescription('Set up verify panel')
        ->addOption((new Option($discord))
          ->setName('role')
          ->setDescription('Role granted to verified user')
          ->setType(Option::ROLE)
          ->setRequired(true)
        )->toArray()
    )
  );
});

$client->on(Event::INTERACTION_CREATE, function (Interaction $interaction, Discord $discord) {
    if ($interaction->type == 3) {
      if ($interaction->data->custom_id == "verify") {
        $token = md5(mt_rand());
        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed(["title" => "Please verify at the site below", "description" => "https://verify.pkom.ml/?token=" . $token . ""]), true);
        $filename = "data/token/" . $token . '.json';
        file_put_contents($filename, json_encode(array(
          'user' => $interaction->user->id,
          'guild' => $interaction->guild->id,
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
      }
    }
});

$button = Button::new(Button::STYLE_PRIMARY)->setLabel('Verify')->setCustomId("verify");

$client->listenCommand('setup', function (Interaction $interaction) {
  global $client;
  global $button;
  
  $embed = [
    "title" => "Verification", 
    "description" => "Please verify to give you role",
  ];
  $interaction->channel->sendMessage(MessageBuilder::new()->addEmbed($embed)->addComponent(ActionRow::new()->addComponent($button)));
  $interaction->respondWithMessage(MessageBuilder::new()->setContent("Placed the verify panel"), true);

  $filename = "data/guild/" . $interaction->guild->id . '.json';
  $data = array();
  if (file_exists($filename)) {
    $data = json_decode(file_get_contents($filename), true);
  }

  $data["id"] = $interaction->guild->id;
  $data["role"] = $interaction->data->resolved->roles->first()->id;
  
  file_put_contents($filename, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
});


$client->run();