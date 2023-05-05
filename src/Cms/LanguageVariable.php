<?php

namespace Kirby\Cms;

use Kirby\Exception\DuplicateException;
use Kirby\Toolkit\Str;

/**
 * A language variable is a custom translation string
 * Those are stored in /site/languages/$code.php in the
 * translations array
 *
 * @package   Kirby Cms
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://getkirby.com/license
 */
class LanguageVariable
{
	protected App $kirby;

	public function __construct(protected Language $language, protected string $key)
	{
		$this->kirby = App::instance();
	}

	/**
	 * Creates a new language variable. This will
	 * be added to the default language first and
     * can then be translated in other languages.
	 */
	public static function create(string $key, string|null $value = null): static
	{
		$key          = Str::slug($key);
		$value        = trim($value ?? '');
		$kirby        = App::instance();
		$language     = $kirby->defaultLanguage();
		$variable     = $language->variable($key);
		$translations = $language->translations();

		if ($kirby->translation()->get($key) !== null) {
			if (isset($translations[$key]) === true) {
				throw new DuplicateException('The variable already exists');
			} else {
				throw new DuplicateException('The variable is part of the core translation and cannot be overwritten');
			}
		}

		$translations[$key] = $value;

		$language->update([
			'translations' => $translations
		]);

		return $language->variable($key);
	}

	/**
	 * Deletes a language variable from the translations array.
     * This will go through all language files and delete the
     * key from all translation arrays to keep them clean.
	 */
	public function delete(): bool
	{
		// go through all languages and remove the variable
		foreach ($this->kirby->languages() as $language) {
			$variables = $language->translations();

			unset($variables[$this->key]);

			$language->update([
				'translations' => $variables
			]);
		}

		return true;
	}

	/**
	 * Checks if a language variable exists in the default language
	 */
	public function exists(): bool
	{
		return isset($this->kirby->defaultLanguage()->translations()[$this->key]) === true;
	}

	/**
	 * Returns the unique key for the variable
	 */
	public function key(): string
	{
		return $this->key;
	}

	/**
	 * Sets a new value for the language variable
	 */
	public function update(string $value): static
	{
		$translations = $this->language->translations();
		$translations[$this->key] = $value;

		return $this->language->update(['translations' => $translations])->variable($this->key);
	}

	/**
	 * Returns the value if the variable has been translated.
	 */
	public function value(): ?string
	{
		return $this->language->translations()[$this->key] ?? null;
	}
}
