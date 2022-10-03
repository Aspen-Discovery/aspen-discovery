<?php

namespace League\OAuth2\Client\Provider;

use Logger;
use SSOSetting;
use SSOMapping;

class GoogleUser implements ResourceOwnerInterface
{
	/**
	 * @var array
	 */
	protected $response;

	private string $matchpoint_id;
	private string $matchpoint_email;
	private string $matchpoint_firstname;
	private string $matchpoint_lastname;

	/**
	 * @param array $response
	 */
	public function __construct(array $response)
	{
		$this->response = $response;
		$this->setMatchpoints();
	}

	public function getId()
	{
		return $this->response[$this->matchpoint_id ?? 'sub'];
	}

	/**
	 * Get preferred display name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->response['name'];
	}

	/**
	 * Get preferred first name.
	 *
	 * @return string|null
	 */
	public function getFirstName(): ?string
	{
		return $this->getResponseValue($this->matchpoint_firstname ?? 'given_name');
	}

	/**
	 * Get preferred last name.
	 *
	 * @return string|null
	 */
	public function getLastName(): ?string
	{
		return $this->getResponseValue($this->matchpoint_lastname ?? 'family_name');
	}

	/**
	 * Get locale.
	 *
	 * @return string|null
	 */
	public function getLocale(): ?string
	{
		return $this->getResponseValue('locale');
	}

	/**
	 * Get email address.
	 *
	 * @return string|null
	 */
	public function getEmail(): ?string
	{
		return $this->getResponseValue($this->matchpoint_email ?? 'email');
	}

	/**
	 * Get hosted domain.
	 *
	 * @return string|null
	 */
	public function getHostedDomain(): ?string
	{
		return $this->getResponseValue('hd');
	}

	/**
	 * Get avatar image URL.
	 *
	 * @return string|null
	 */
	public function getAvatar(): ?string
	{
		return $this->getResponseValue('picture');
	}

	/**
	 * Get user data as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->response;
	}

	private function getResponseValue($key)
	{
		return $this->response[$key] ?? null;
	}

	private function setMatchpoints()
	{
		global $library;
		$settings = new SSOSetting();
		$settings->id = $library->ssoSettingId;
		$settings->service = "oauth";
		if ($settings->find(true)) {
			$mappings = new SSOMapping();
			$mappings->ssoSettingId = $settings->id;
			$mappings->find();
			while ($mappings->fetch()) {
				if ($mappings->aspenField == "email") {
					$this->matchpoint_email = $mappings->responseField;
				} elseif ($mappings->aspenField == "user_id") {
					$this->matchpoint_id = $mappings->responseField;
				} elseif ($mappings->aspenField == "first_name") {
					$this->matchpoint_firstname = $mappings->responseField;
				} elseif ($mappings->aspenField == "last_name") {
					$this->matchpoint_lastname = $mappings->responseField;
				}
			}
		}
	}
}
