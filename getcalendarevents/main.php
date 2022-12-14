<?php

use Beta\Microsoft\Graph\Model\Message;

require_once realpath(__DIR__ . '/vendor/autoload.php');
require_once 'GraphHelper.php';

print('Get Calendar Events'.PHP_EOL.PHP_EOL);

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['CLIENT_ID', 'CLIENT_SECRET', 'TENANT_ID', 'AUTH_TENANT', 'GRAPH_USER_SCOPES']);

initializeGraph();

greetUser();

$choice = -1;

while ($choice != 0) {
    echo('Please choose one of the following options:'.PHP_EOL);
    echo('0. Exit'.PHP_EOL);
    echo('1. Display access token'.PHP_EOL);
    echo('2. List my inbox'.PHP_EOL);
    echo('3. Send mail'.PHP_EOL);
    echo('4. List users (requires app-only)'.PHP_EOL);
    echo('5. List calendar events'.PHP_EOL);

    $choice = (int)readline('');

    switch ($choice) {
        case 1:
            displayAccessToken();
            break;
        case 2:
            listInbox();
            break;
        case 3:
            sendMail();
            break;
        case 4:
            listUsers();
            break;
        case 5:
            getCalendarEvents();
            break;
        case 0:
        default:
            print('Goodbye...'.PHP_EOL);
    }
}

function initializeGraph() {
    GraphHelper::initializeGraphForUserAuth();
}

function greetUser() {
    try {
        $user = GraphHelper::getUser();
        print('Hello, '.$user->getDisplayName().'!'.PHP_EOL);

        // For Work/school accounts, email is in Mail property
        // Personal accounts, email is in UserPrincipalName
        $email = $user->getMail();
        if (empty($email)) {
            $email = $user->getUserPrincipalName();
        }
        print('Email: '.$email.PHP_EOL.PHP_EOL);
    } catch (Exception $e) {
        print('Error getting user: '.$e->getMessage().PHP_EOL.PHP_EOL);
    }
}

function displayAccessToken() {
    try {
        $token = GraphHelper::getUserToken();
        print('User token: '.$token.PHP_EOL.PHP_EOL);
    } catch (Exception $e) {
        print('Error getting access token: '.$e->getMessage().PHP_EOL.PHP_EOL);
    }
}

function listInbox() {
    try {
        $messages = GraphHelper::getInbox();

        // Output each message's details
        foreach ($messages->getPage() as $message) {
            print('Message: '.$message->getSubject().PHP_EOL);
            print('  From: '.$message->getFrom()->getEmailAddress()->getName().PHP_EOL);
            $status = $message->getIsRead() ? "Read" : "Unread";
            print('  Status: '.$status.PHP_EOL);
            print('  Received: '.$message->getReceivedDateTime()->format(\DateTimeInterface::RFC2822).PHP_EOL);
        }

        $moreAvailable = $messages->isEnd() ? 'False' : 'True';
        print(PHP_EOL.'More messages available? '.$moreAvailable.PHP_EOL.PHP_EOL);
    } catch (Exception $e) {
        print('Error getting user\'s inbox: '.$e->getMessage().PHP_EOL.PHP_EOL);
    }
}

function sendMail() {
    try {
        // Send mail to the signed-in user
        // Get the user for their email address
        $user = GraphHelper::getUser();

        // For Work/school accounts, email is in Mail property
        // Personal accounts, email is in UserPrincipalName
        $email = $user->getMail();
        if (empty($email)) {
            $email = $user->getUserPrincipalName();
        }

        GraphHelper::sendMail('Testing Microsoft Graph', 'Hello world!', $email);

        print(PHP_EOL.'Mail sent.'.PHP_EOL.PHP_EOL);
    } catch (Exception $e) {
        print('Error sending mail: '.$e->getMessage().PHP_EOL.PHP_EOL);
    }
}

function listUsers() {
    try {
        $users = GraphHelper::getUsers();

        // Output each user's details
        foreach ($users->getPage() as $user) {
            print('User: '.$user->getDisplayName().PHP_EOL);
            print('  ID: '.$user->getId().PHP_EOL);
            $email = $user->getMail();
            $email = isset($email) ? $email : 'NO EMAIL';
            print('  Email: '.$email.PHP_EOL);
        }

        $moreAvailable = $users->isEnd() ? 'False' : 'True';
        print(PHP_EOL.'More users available? '.$moreAvailable.PHP_EOL.PHP_EOL);
    } catch (Exception $e) {
        print(PHP_EOL.'Error getting users: '.$e->getMessage().PHP_EOL.PHP_EOL);
    }
}

function getCalendarEvents() {
    try {
        $message = GraphHelper::getCalendars();
        $ids = [];
        $names = [];
        $number = 0;
        foreach ($message->getPage() as $calendar) {
            print('No: '. ++ $number .PHP_EOL);
            print('Name: '.$calendar->getName().PHP_EOL);
            print('  ID: '.$calendar->getId().PHP_EOL);
            print('  Owner: '.PHP_EOL);
            $ids[] = $calendar->getId();
            $names[] = $calendar->getName();
            $owner = $calendar->getOwner();
            if (!isset($owner)) {
                continue;
            }
            $owner_name = $owner->getName();
            $owner_name = isset($owner_name) ? $owner_name : 'NO NAME';
            $owner_address = $owner->getAddress();
            $owner_address = isset($owner_address) ? $owner_address : 'NO EMAIL';
            print('      name: '.$owner_name.PHP_EOL);
            print('      address: '.$owner_address.PHP_EOL);
        }

        $number = 0;

        echo('Please choose an number of the above Calendars.'.PHP_EOL);
        echo('Select 0 to return main menu...'.PHP_EOL);
    
        $number = (int)readline('');

        if ($number < 1 || $number > count($ids)) {
            print('Invalid number'.PHP_EOL);
            return;
        }
        $id = $ids[$number - 1];
        $name = $names[$number - 1];

        print('Selected Calendar Name: '.$name.PHP_EOL);
        print('   Calendar ID: '.$id.PHP_EOL);
        print(PHP_EOL);

        $events = GraphHelper::getCalendarEvents($id);

        foreach ($events->getPage() as $event) {
            print('Subject: '.$event->getSubject().PHP_EOL);
            print('  BodyPreview: '.$event->getBodyPreview().PHP_EOL);
            print('  Start Time: '.$event->getStart()->getDateTime().PHP_EOL);
            print('  end Time: '.$event->getEnd()->getDateTime().PHP_EOL);
            $organizer = $event->getOrganizer();
            $email_address = $organizer->getEmailAddress();
            $name = $email_address->getName();
            $name = isset($name) ? $name : 'NO NAME';
            $address = $email_address->getAddress();
            $address = isset($address) ? $address : 'NO EMAIL';
            print('  Organizer:'.PHP_EOL);
            print('      name: '.$name.PHP_EOL);
            print('      address: '.$address.PHP_EOL);
            $ids[] = $calendar->getId();
            $names[] = $calendar->getName();
        }
    } catch (Exception $e) {
        print(PHP_EOL.$e->getMessage().PHP_EOL.PHP_EOL);
    }
}
?>
