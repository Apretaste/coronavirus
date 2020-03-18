<?php

use Framework\Core;
use Framework\Crawler;
use Apretaste\Request;
use Apretaste\Response;

class Service
{
	/**
	 * Show summary of cases
	 *
	 * @param Request
	 * @param Response
	 */
	public function _main(Request $request, Response &$response)
	{
		// get JSON data
		$data = $this->getJSONDataForToday();

		// get content data
		$content = [
			"updated" => date('d/m/Y h:i a', strtotime($data->confirmed->last_updated)),
			"confirmed" => number_format($data->confirmed->latest),
			"deaths" => number_format($data->deaths->latest),
			"recovered" => number_format($data->recovered->latest)
		];

		// send data to the view
		$response->setCache('day');
		$response->setTemplate('resumen.ejs', $content);
	}

	/**
	 * Show infested by country
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _enfermos(Request $request, Response &$response)
	{
		// get array of confirmed
		$items = $this->getArrayByCountry('confirmed');

		// send data to the view
		$response->setCache('day');
		$response->setTemplate('enfermos.ejs', ["items" => $items]);
	}

	/**
	 * Show deaths by country
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _muertes(Request $request, Response &$response)
	{
		// get array of confirmed
		$items = $this->getArrayByCountry('deaths');

		// send data to the view
		$response->setCache('day');
		$response->setTemplate('muertes.ejs', ["items" => $items]);
	}

	/**
	 * Show recovered by country
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _recuperados(Request $request, Response &$response)
	{
		// get array of confirmed
		$items = $this->getArrayByCountry('recovered');

		// send data to the view
		$response->setCache('day');
		$response->setTemplate('recuperados.ejs', ["items" => $items]);
	}

	/**
	 * Get the JSON of cases from cache or from the internet
	 *
	 * @return JSON
	 */
	private function getJSONDataForToday()
	{
		// get content from cache
		$cache = TEMP_PATH . "cache/coronavirus" . date("Ymd") . ".cache";
		if (file_exists($cache)) {
			$data = unserialize(file_get_contents($cache));
		}

		// crawl the data from the web
		else {
			// get the JSON data
			$data = Crawler::get('https://coronavirus-tracker-api.herokuapp.com/all');
			$data = json_decode($data);

			// create the cache
			file_put_contents($cache, serialize($data));
		}

		// return JSON as object
		return $data;
	}

	/**
	 * Get the array of confirmed, deaths or recovered by countries
	 *
	 * @param Enum $type, confirmed|deaths|recovered
	 * @return Array
	 */
	private function getArrayByCountry($type)
	{
		// get JSON data
		$data = $this->getJSONDataForToday();

		// remove unused data
		$items = [];
		foreach ($data->$type->locations as $dt) {
			// do not add "other" areas
			if($dt->country_code == 'XX') {
				continue;
			}

			// make ths country code lowercase
			$countryCode = strtolower($dt->country_code);

			// if a country's province was already added, update the total
			if(isset($items[$countryCode])) {
				$items[$countryCode]->total += $dt->latest;
				continue;
			}

			// get the country name in Spanish, of possible
			$countryName = isset(Core::$countries[$dt->country_code]) ? Core::$countries[$dt->country_code] : $dt->country;

			// add the new country
			$item =  new \stdClass();
			$item->total = $dt->latest;
			$item->countryCode = $countryCode;
			$item->countryName = $countryName;
			$items[$countryCode] = $item;
		}

		// sort countries
		function cmp($a, $b) {
			return $a->total < $b->total;
		}
		usort($items, "cmp");

		// return the final array
		return $items;
	}
}
