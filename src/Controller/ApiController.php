<?php

namespace App\Controller;

use App\Model\AbstractManager;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;
use DateTime;

class ApiController extends AbstractController
{
    private string $parametersAstronomy = 'sunrise,sunset,moonPhrase';
    private string $parametersWeather =
        'airTemperature,cloudCover,currentDirection,currentSpeed,iceCover,waterTemperature,waveHeight,windDirection,windSpeed,visibility,precipitation';
    private string $source = 'sg';

    public function travelDuration($secondDate): string
    {
        $first = new Datetime();
        $second = new Datetime($secondDate);
        $interval = floor(($second->getTimestamp() - $first->getTimestamp()) / 3600);
        return $interval;
    }

    public function marineAPI($lat, $long, $params, $type)
    {

        $clientWeather = HttpClient::create([
            'headers' => [
                'Authorization' => APP_API_KEY,
            ],
        ]);
        $response = $clientWeather->request(
            'GET',
            'https://api.stormglass.io/v2/' . $type . '/point?lat=' .
            $lat . '&lng=' . $long . '&params=' . $params . '&source=' . $this->source
        );

        return $response->toArray();
    }

    public function getWeather()
    {

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!empty($_POST['leave']) && !empty($_POST['arrive'])){
                if (isset($_SESSION['departurelat']) && isset($_SESSION['destinationlat'])){
            $lat1 = $_SESSION['departurelat'];
            $long1 = $_SESSION['departurelong'];
            $lat2 =  $_SESSION['destinationlat'];
            $long2 = $_SESSION['destinationlong'];
            $leave = $_POST['leave'];
            $arrive = $_POST['arrive'];
            $hourToDest = $this->travelDuration($arrive);
            $hourToLeave = $this->travelDuration($leave);
            $daystoDest = floor($hourToDest / 24);
            $hoursdest = $hourToDest - $daystoDest * 24;
            $daystoDep = floor($hourToLeave / 24);
            $hoursdep = $hourToLeave - $daystoDep * 24;
            $_SESSION['checkdata'] = "";

            if ($hourToLeave <= 241 && $hourToDest <= 240) {
                if (isset($this->marineAPI($lat1, $long1, $this->parametersWeather, 'weather')["hours"][$hourToLeave])) {
                    $weather_l = $this->marineAPI($lat1, $long1, $this->parametersWeather, 'weather')["hours"][$hourToLeave];
                    $weather_d = $this->marineAPI($lat2, $long2, $this->parametersWeather, 'weather')["hours"][$hourToDest];
                    $astr_l = $this->marineAPI($lat1, $long1, $this->parametersAstronomy, 'astronomy')["data"][$hourToLeave / 24];
                    $astr_d = $this->marineAPI($lat2, $long2, $this->parametersAstronomy, 'astronomy')["data"][$hourToDest / 24];


                    $sunrise_l = new Datetime($astr_l['sunrise']);
                    $astr_l['sunrise'] = date_format($sunrise_l, 'H:i:s');

                    $sunset_l = new Datetime($astr_l['sunset']);
                    $astr_l['sunset'] = date_format($sunset_l, 'H:i:s');

                    $sunrise_d = new Datetime($astr_d['sunrise']);
                    $astr_d['sunrise'] = date_format($sunrise_d, 'H:i:s');

                    $sunset_d = new Datetime($astr_d['sunset']);
                    $astr_d['sunset'] = date_format($sunset_d, 'H:i:s');

                    if (isset($weather_l["currentDirection"]["sg"])) {
                        $currentDirection_l = $weather_l["currentDirection"]["sg"];
                        $currentSpeed_l = $weather_l["currentSpeed"]["sg"];
                        $currentDirection_d = $weather_d["currentDirection"]["sg"];
                        $currentSpeed_d = $weather_d["currentSpeed"]["sg"];
                        $windDirection_l = $weather_l["windDirection"]["sg"];
                        $windSpeed_l = $weather_l["windSpeed"]["sg"];
                        $windDirection_d = $weather_d["windDirection"]["sg"];
                        $windSpeed_d = $weather_d["windSpeed"]["sg"];

                        return $this->twig->render('Home/index.html.twig', [
                            'weather_data_leave' => $weather_l,
                            'weather_data_dest' => $weather_d,
                            'astronomy_data_leave' => $astr_l,
                            'astronomy_data_dest' => $astr_d,
                            'hour_to_dest' => $hourToDest,
                            'hour_to_leave' => $hourToLeave,
                            'lat_departure' => $lat1,
                            'long_departure' => $long1,
                            'lat_destination' => $lat2,
                            'long_destination' => $long2,
                            'wind_direction_departure' => $windDirection_l,
                            'wind_direction_destination' => $windDirection_d,
                            'current_direction_departure' => $currentDirection_l,
                            'current_direction_destination' => $currentDirection_d,
                            'wind_speed_departure' => $windSpeed_l,
                            'wind_speed_destination' => $windSpeed_d,
                            'current_speed_departure' => $currentSpeed_l,
                            'current_speed_destination' => $currentSpeed_d,
                            'days_dep' => $daystoDep,
                            'hours_dep' => $hoursdep,
                            'days_dest' => $daystoDest,
                            'hours_dest' => $hoursdest,

                        ]);
                    }else{
                        session_destroy();
                        session_start();
                        $errormessage = "You seem to try to go to non-navigable places. Please choose another departure / destination point.";
                        return $this->twig->render('Home/index.html.twig', [
                            'time_error' => $errormessage,
                        ]);
                    }
                } else {
                    session_destroy();
                    session_start();
                    $errormessage = "You seem to try to go to non-navigable places. Please choose another departure / destination point.";
                    return $this->twig->render('Home/index.html.twig', [
                        'time_error' => $errormessage,
                    ]);
                }
            } else {
                session_destroy();
                session_start();
                $errormessage = "Sorry, we cannot provide marine forecast more than 10 days ahead. Please choose another departure/destination time, within 10 days.";
                return $this->twig->render('Home/index.html.twig', [
                    'time_error' => $errormessage,
                    ]);
            }} else{
                session_destroy();
                session_start();
                $errormessage = "Please select departure and destination points and time.";
                return $this->twig->render('Home/index.html.twig', [
                    'time_error' => $errormessage,
                ]);

            }
            }else{
                session_destroy();
                session_start();
                $errormessage = "Please select departure and destination points and time.";
                return $this->twig->render('Home/index.html.twig', [
                    'time_error' => $errormessage,
                ]);

            }

        }
    }


    /**
     * @return string
     */
    public function getParams(): string
    {
        return $this->params;
    }

    /**
     * @param string $params
     */
    public function setParams(string $params): void
    {
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getParameters(): string
    {
        return $this->parameters;
    }

    /**
     * @param string $parameters
     */
    public function setParameters(string $parameters): void
    {
        $this->parameters = $parameters;
    }
}
