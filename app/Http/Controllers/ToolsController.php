<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\WeatherData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolsController extends Controller
{
    /**
     * Muestra la herramienta de mapas
     */
    public function maps(): View
    {
        $locations = Location::where('active', true)->get();
        
        return view('tools.maps', compact('locations'));
    }

    /**
     * Muestra la herramienta de clima
     */
    public function weather(): View
    {
        $locations = Location::where('active', true)->with('latestWeatherData')->get();
        $recentWeatherData = WeatherData::with('location')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('tools.weather', compact('locations', 'recentWeatherData'));
    }

    /**
     * Muestra la herramienta de análisis
     */
    public function analytics(): View
    {
        $stats = [
            'total_locations' => Location::count(),
            'active_locations' => Location::where('active', true)->count(),
            'total_weather_records' => WeatherData::count(),
            'recent_weather_updates' => WeatherData::whereDate('created_at', today())->count(),
        ];
        
        return view('tools.analytics', compact('stats'));
    }
}
