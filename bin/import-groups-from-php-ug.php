#!/usr/bin/env php
<?php declare(strict_types=1);

use AllFriensOfPhp\UserGroupRepository;
use AllFriensOfPhp\YamlMeetupFileManager;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Rinvex\Country\CountryLoader;

require __DIR__ . '/../vendor/autoload.php';

$client = new GuzzleHttp\Client([
    'headers' => [
        'Accept' => 'application/json'
    ]
]);

$url = sprintf('https://php.ug/api/rest/listtype/1');
$response = $client->get($url);

$result = Json::decode($response->getBody(), Json::FORCE_ARRAY);

$groups = $result['groups'];

$meetupGroups[] = [];
foreach ($groups as $group) {
    // resolve meetups.com groups first
    if (! Strings::contains($group['url'], 'meetup.com')) {
        continue;
    }

    if ($group['country']) {
        $country = (CountryLoader::country($group['country']))->getName();

    } else {
        // detect city + country from latitude/longitude
        $latitude = $group['latitude'];
        $longitude = $group['longitude'];

        $geocode = file_get_contents(sprintf(
            'http://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&sensor=false',
            $latitude,
            $longitude
        ));

        $geocodeJson = Json::decode($geocode, Json::FORCE_ARRAY);

        if ($geocodeJson['status'] !== 'OK') {
            $country = null;
        } else {
            foreach ($geocodeJson['results'][0]['address_components'] as $addressComponent) {
                if (in_array('country', $addressComponent['types'], true)) {
                    $country = $addressComponent['long_name'];
                    break;
                }
            }
        }

        // Germany, Europe
    }

    $meetupGroups[] = [
        'meetup_com_url' => $group['url'],
        'country' => $country,
    ];
}

$userGroupRepository = (new UserGroupRepository());
$userGroupRepository->saveToFile($meetupGroups);
