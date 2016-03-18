<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\NetteSlackLogger;

interface IMessage
{

	/**
	 * @return string
	 */
	function getChannel();


	/**
	 * @param string $channel
	 * @return $this
	 */
	function setChannel($channel);

	/**
	 * @return string
	 */
	function getIcon();

	/**
	 * @param string $icon
	 * @return $this
	 */
	function setIcon($icon);

	/**
	 * @return string
	 */
	function getName();

	/**
	 * @param string $name
	 * @return $this
	 */
	function setName($name);

	/**
	 * @return string
	 */
	function getTitle();

	/**
	 * @param string $title
	 * @return $this
	 */
	function setTitle($title);

	/**
	 * @return string
	 */
	function getText();

	/**
	 * @param string $text
	 * @return $this
	 */
	function setText($text);

	/**
	 * @return string
	 */
	function getColor();

	/**
	 * @param string $color
	 * @return $this
	 */
	function setColor($color);

}
