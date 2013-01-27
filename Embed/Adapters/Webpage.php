<?php
namespace Embed\Adapters;

use Embed\Url;
use Embed\Providers\Provider;
use Embed\Providers\Html;
use Embed\Providers\OEmbed;
use Embed\Providers\OEmbedImplementations;
use Embed\Providers\OpenGraph;
use Embed\Providers\TwitterCards;

class Webpage {
	static public $settings = array();

	static public function check (Url $Url) {
		return $Url;
	}

	public function __construct (Url $Url, $followCanonical = true) {
		$this->Url = $Url;
		$this->Html = new Html($Url);
		$this->OpenGraph = new OpenGraph($Url);
		$this->TwitterCards = new TwitterCards($Url);

		if ($this->Html->get('oembed')) {
			$this->OEmbed = new OEmbed(new Url($Url->getAbsolute($this->Html->get('oembed'))));
		} else {
			$this->OEmbed = OEmbedImplementations::create($Url);
		}

		if (!$this->OEmbed) {
			$this->OEmbed = new Provider;
		}

		$this->setData();

		if ($followCanonical && ($Url->getUrl() !== $this->url)) {
			static::__construct(new Url($this->url), false);
		}
	}

	protected function setData () {
		$properties = array(
			'title',
			'description',
			'type',
			'code',
			'url',
			'authorName',
			'authorUrl',
			'providerIcon',
			'providerName',
			'providerUrl',
			'image',
			'width',
			'height',
			'aspectRatio'
		);

		foreach ($properties as $name) {
			$method = 'get'.$name;

			$this->$name = $this->OEmbed->$method() ?: $this->OpenGraph->$method() ?: $this->TwitterCards->$method() ?: $this->Html->$method();
		}

		//Calculate aspect ratio
		if ($this->width && (strpos($this->width, '%') === false) && $this->height && (strpos($this->height, '%') === false)) {
			$this->aspectRatio = round(($this->height / $this->width) * 100, 3);
		}

		//Clear extra code
		if (($html = $this->code)) {
			if (strpos($html, '</iframe>') !== false) {
				$html = preg_replace('|^.*(<iframe.*</iframe>).*$|', '$1', $html);
			} else if (strpos($html, '</object>') !== false) {
				$html = preg_replace('|^.*(<object.*</object>).*$|', '$1', $html);
			} else if (strpos($html, '</embed>') !== false) {
				$html = preg_replace('|^.*(<embed.*</embed>).*$|', '$1', $html);
			}

			$this->code = $html;
		}

		//Calculate url properties
		if (!$this->url) {
			$this->url = $this->Url->getUrl();
		}

		if (!$this->providerName) {
			$this->providerName = $this->Url->getDomain();
		}

		if (!$this->providerUrl) {
			$this->providerUrl = $this->Url->getScheme().'://'.$this->Url->getHost();
		}
	}
}
