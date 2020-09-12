<?php

use Framework\Core;
use Framework\Crawler;
use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Challenges;

class Service
{
	/**
	 * Show summary of cases
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws \Framework\Alert
	 */
	public function _main(Request $request, Response $response)
	{
		// get JSON data
		$data = $this->getJSONDataForToday();

		// get content data
		$content = [
			"timestamp" => $data->timestamp,
			"cases" => $data->cases,
			"deaths" => $data->deaths,
			"recovered" => $data->recovered,
			"tests" => $data->tests
		];

		Challenges::complete('view-coronavirus', $request->person->id);

		// send data to the view
		$response->setCache('day');
		$response->setTemplate('resumen.ejs', $content);
	}

	/**
	 * Show all the data by country
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws \Framework\Alert
	 */
	public function _paises(Request $request, Response $response)
	{
		// get array of confirmed
		$data = $this->getJSONDataForToday();

		// send data to the view
		$response->setCache('day');
		$response->setTemplate('paises.ejs', ["countries" => $data->countries]);
	}

	/**
	 * Show recovered by country
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws \Framework\Alert
	 */
	public function _recuperados(Request $request, Response $response)
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
	 * @return string
	 * @throws \Framework\Alert
	 */
	private function getJSONDataForToday()
	{
		// get content from cache
		$cache = TEMP_PATH . "cache/coronavirus_" . date("Ymd") . ".cache";
		if (file_exists($cache)) {
			$data = unserialize(file_get_contents($cache));
		}

		// crawl the data from the web
		else {
			// get the JSON data
			$data = Crawler::get('https://www.covidvisualizer.com/api');
			$data = json_decode($data);

			// format the data
			$data = $this->formatDataArray($data);

			// create the cache
			file_put_contents($cache, serialize($data));
		}

		// return JSON as object
		return $data;
	}

	/**
	 * Formats the data to a friendly structure
	 *
	 * @return string
	 */
	private function formatDataArray($data)
	{
		// create data object
		$dt = new stdClass();
		$dt->timestamp = $data->timestamp;
		$dt->cases = 0;
		$dt->deaths = 0;
		$dt->recovered = 0;
		$dt->tests = 0;
		$dt->countries = [];

		// format country data
		foreach ($data->countries as $key => $val) {
			// format the country data
			$country = new stdClass();
			$country->code = isset(Core::$countries[$key]) ? strtolower($key) : "";
			$country->name = isset(Core::$countries[$key]) ? Core::$countries[$key] : $val->name;
			$country->population = $val->population;
			$country->cases = $val->cases;
			$country->deaths = $val->deaths;
			$country->recovered = $val->recovered;
			$country->deathsPerOneMillion = $val->deathsPerOneMillion;
			$country->totalTests = $val->totalTests;

			// add country data to main array
			$dt->cases += $country->cases;
			$dt->deaths += $country->deaths;
			$dt->recovered += $country->recovered;
			$dt->tests += $country->totalTests;
			$dt->countries[] = $country;
		}

		// sort by death per million
		function cmp($a, $b)
		{
			return $a->deathsPerOneMillion < $b->deathsPerOneMillion;
		}
		usort($dt->countries, "cmp");

		return $dt;
	}
}
