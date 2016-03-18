<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger;

class Message implements IMessage
{

	/** @var string */
	private $channel;

	/** @var string */
	private $icon;

	/** @var string */
	private $name;

	/** @var string */
	private $title;

	/** @var string */
	private $text;

	/** @var string */
	private $color;


	public function __construct(array $defaults)
	{
		foreach (['channel', 'icon', 'name', 'title', 'text', 'color'] as $property) {
			if (isset($defaults[$property])) {
				$this->$property = $defaults[$property];
			}
		}
	}


	/**
	 * @inheritdoc
	 */
	public function getChannel()
	{
		return $this->channel;
	}


	/**
	 * @inheritdoc
	 */
	public function setChannel($channel)
	{
		$this->channel = (string) $channel;
		return $this;
	}


	/**
	 * @inheritdoc
	 */
	public function getIcon()
	{
		return $this->icon;
	}


	/**
	 * @inheritdoc
	 */
	public function setIcon($icon)
	{
		$this->icon = (string) $icon;
		return $this;
	}


	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @inheritdoc
	 */
	public function setName($name)
	{
		$this->name = (string) $name;
		return $this;
	}


	/**
	 * @inheritdoc
	 */
	public function getTitle()
	{
		return $this->title;
	}


	/**
	 * @inheritdoc
	 */
	public function setTitle($title)
	{
		$this->title = (string) $title;
		return $this;
	}


	/**
	 * @inheritdoc
	 */
	public function getText()
	{
		return $this->text;
	}


	/**
	 * @param string $text
	 * @return $this
	 */
	public function setText($text)
	{
		$this->text = (string) $text;
		return $this;
	}


	/**
	 @inheritdoc
	 */
	public function getColor()
	{
		return $this->color;
	}


	/**
	 * @param string $color
	 * @return $this
	 */
	public function setColor($color)
	{
		$this->color = (string) $color;
		return $this;
	}

}
