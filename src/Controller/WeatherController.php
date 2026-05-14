<?php

namespace App\Controller;

use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/meteo')]
class WeatherController extends AbstractController
{
    #[Route('/', name: 'app_weather_index')]
    public function index(WeatherService $weatherService): Response
    {
        $allWeather = $weatherService->getAllWeather5Days();
<<<<<<< HEAD
       
=======
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        $regions = [];
        foreach ($allWeather as $weather) {
            $region = $weather['region'];
            if (!isset($regions[$region])) {
                $regions[$region] = [];
            }
            $regions[$region][] = $weather;
        }
<<<<<<< HEAD
       
=======
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        return $this->render('weather/index.html.twig', [
            'regions' => $regions,
            'last_update' => new \DateTime()
        ]);
    }
<<<<<<< HEAD
   
=======
    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Route('/ville/{city}', name: 'app_weather_city')]
    public function city(string $city, WeatherService $weatherService, Request $request): Response
    {
        $weather = $weatherService->getWeather5Days($city);
<<<<<<< HEAD
       
        if (!$weather) {
            throw $this->createNotFoundException('Ville non trouvée');
        }
       
        $specificDate = $request->query->get('date');
        $specificWeather = null;
       
        // Correction : Vérifier que $specificDate est une chaîne de caractères
        if (is_string($specificDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $specificDate)) {
            $specificWeather = $weatherService->getWeatherByDate($city, $specificDate);
        }
       
=======
        
        if (!$weather) {
            throw $this->createNotFoundException('Ville non trouvée');
        }
        
        $specificDate = $request->query->get('date');
        $specificWeather = null;
        
        if ($specificDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $specificDate)) {
            $specificWeather = $weatherService->getWeatherByDate($city, $specificDate);
        }
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        return $this->render('weather/city.html.twig', [
            'weather' => $weather,
            'specific_date' => $specificDate,
            'specific_weather' => $specificWeather,
            'cities' => $weatherService->getAllCities(),
        ]);
    }
<<<<<<< HEAD
   
=======
    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Route('/api/weather/{city}', name: 'app_weather_api')]
    public function api(string $city, WeatherService $weatherService, Request $request): Response
    {
        $specificDate = $request->query->get('date');
<<<<<<< HEAD
       
        // Correction : Vérifier que $specificDate est une chaîne de caractères
        if (is_string($specificDate)) {
=======
        
        if ($specificDate) {
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
            $weather = $weatherService->getWeatherByDate($city, $specificDate);
        } else {
            $weather = $weatherService->getWeather5Days($city);
        }
<<<<<<< HEAD
       
        return $this->json($weather);
    }
   
=======
        
        return $this->json($weather);
    }
    
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
    #[Route('/debug', name: 'app_weather_debug')]
    public function debug(WeatherService $weatherService): Response
    {
        $test = $weatherService->getWeather5Days('Tunis');
<<<<<<< HEAD
       
=======
        
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
        return $this->json([
            'api_test' => $test ? 'API a répondu' : 'API n\'a pas répondu - utilisation des données de secours',
            'data_received' => $test !== null,
            'total_cities' => count($weatherService->getAllCities()),
            'weather_data' => $test
        ]);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 1c94a897d2f9442710693a83ad2d8e675fdf34eb
