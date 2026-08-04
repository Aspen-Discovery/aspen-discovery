<?php

class HeyCentricUrlBuilder {
	private string $baseUrl;
	private string $privateKey;
	private array $outputParams;
	private array $hashParams;
	private array $paramsToHash;

	public function __construct(string $baseUrl, string $privateKey, array $paramsToHash) {
		$this->baseUrl = $baseUrl;
		$this->privateKey = $privateKey;
		$this->outputParams = [];
		$this->hashParams = [];
		$this->paramsToHash = $paramsToHash;
	}

	public function addParam(string $key, string $value, int $index = 0) : void {
		$suffix = $index == 0 ? '' : ('_' . $index);
		$result = urlencode($key . $suffix) . '=' . urlencode($value);
		$this->outputParams[] = $result;
		if(in_array($key, $this->paramsToHash, true)) {
			$this->hashParams[] = $result;
		}
	}

	public function build() : string {
		$hashInput = implode('&', $this->hashParams) . $this->privateKey;
		$hashOutput = base64_encode(md5($hashInput));

		return $this->baseUrl . implode('&', $this->outputParams) . '&hash=' . urlencode($hashOutput);
	}
}
