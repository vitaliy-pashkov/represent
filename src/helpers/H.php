<?php

/**
 * Created by PhpStorm.
 * User: vitaliy
 * Date: 15.03.2019
 * Time: 5:03
 */

namespace vpashkov\represent\helpers;


class H
	{

	public static function get($array, $path, $nullValue = null)
		{
		$parts = explode('.', $path);
		$key = $parts[0];
		if ($array == null)
			{
			return $nullValue;
			}

		if (is_array($array))
			{
			if (isset($array[ $key ]))
				{
				if (count($parts) <= 1)
					{
					return $array[ $key ];
					}
				else
					{
					unset($parts[0]);
					return H::get($array[ $key ], implode('.', $parts), $nullValue);
					}
				}
			else
				{
				return $nullValue;
				}
			}
		if (is_object($array))
			{
			if (isset($array->$key))
				{
				if (count($parts) <= 1)
					{
					return $array->$key;
					}
				else
					{
					unset($parts[0]);
					return H::get($array->$key, implode('.', $parts), $nullValue);
					}
				}
			else
				{
				return $nullValue;
				}
			}
		}

	public static function set(&$array, $path, &$value)
		{
		$parts = explode('.', $path);
		$key = $parts[0];
		if (array_key_exists($key, $array))
			{
			if (count($parts) <= 1)
				{
				$array[ $key ] = $value;
				}
			else
				{
				unset($parts[0]);
				H::set($array[ $key ], implode('.', $parts), $value);
				}
			}
		else
			{
			$array[ $key ] = [];
			H::set($array[ $key ], implode('.', $parts), $value);
			}
		}

	//	public static function parse($string, $array = [])
	//		{
	//		$offset = 0;
	//		$result = '';
	//		while (strpos($string, '${', $offset) !== false)
	//			{
	//			$start = strpos($string, '${', $offset) + 2;
	//			$finish = strpos($string, '}', $start);
	//
	//			$subTemplate = substr($string, $offset, $start - 2 - $offset);
	//			$key = substr($string, $start, $finish - $start);
	//			$value = H::get($array, $key);
	//			if (strlen($subTemplate) > 0)
	//				{
	//				$result .= $subTemplate;
	//				}
	//			if ($value != null && (is_string($value) || is_numeric($value)))
	//				{
	//				$result .= $value;
	//				}
	//			else
	//				{
	//				$result .= '${' . $key . '}';
	//				}
	//			$offset = $finish + 1;
	//			}
	//		return $result;
	//		}

	public static function currentDate($toFormat = 'Y-m-d')
		{
		return (new \DateTime())->format($toFormat);
		}

	public static function currentDt($toFormat = 'Y-m-d H:i:s')
		{
		return (new \DateTime())->format($toFormat);
		}


	public static function toMysqlDate($date, $fromFormat = 'd.m.Y', $toFormat = 'Y-m-d')
		{
		if ($date == null)
			{
			return null;
			}
		return (\DateTime::createFromFormat($fromFormat, $date))->format($toFormat);
		}


	public static function toRuDate($date, $fromFormat = 'Y-m-d', $toFormat = 'd.m.Y')
		{
		if ($date == null)
			{
			return '';
			}
		return (\DateTime::createFromFormat($fromFormat, $date))->format($toFormat);
		}

	public static function toMysqlDt($date, $fromFormat = 'd.m.Y H:i', $toFormat = 'Y-m-d H:i:s')
		{
		if ($date == null)
			{
			return null;
			}
		return (\DateTime::createFromFormat($fromFormat, $date))->format($toFormat);
		}

	/**
	 *
	 */
	public static function toMysqlDtU($timestamp, $toFormat = 'Y-m-d H:i:s')
		{
		$dtu = explode(".", $timestamp);
		$dt = new \DateTime();
		$dt->setTimestamp($dtu[0]);
		$dt->format($toFormat);
		return $dt->format($toFormat) . "." . (isset($dtu[1]) ? substr($dtu[1], 0, 6) : "000000");
		}

	public static function toRuDt($date, $fromFormat = 'Y-m-d H:i:s', $toFormat = 'd.m.Y H:i')
		{
		if ($date == null)
			{
			return null;
			}
		return (\DateTime::createFromFormat($fromFormat, $date))->format($toFormat);
		}

	public static function getTodayDb($toFormat = 'Y-m-d')
		{
		return (new \DateTime())->format($toFormat);
		}

	public static function parse($string, $context, $nullValue = '')
		{
		$offset = 0;

		while (strpos($string, '${', $offset) == true)
			{
			$begin = strpos($string, '${', $offset) + 2;
			$end = strpos($string, '}', $begin);
			if (strlen($string) - 1 > $begin)
				{
				$fullIndex = substr($string, $begin, $end - $begin);
				$value = H::get($context, $fullIndex, $nullValue);


				$string = str_replace('${' . $fullIndex . '}', $value, $string);
				}
			$offset = $begin + 1;
			if ($offset > strlen($string))
				{
				break;
				}
			}
		return $string;
		}

	public static function phoneFormat($phone)
		{
		if ($phone == null || strlen($phone) != 10)
			{
			return 'телефон не указан';
			}
		$phone = substr_replace($phone, ' ', 3, 0);
		$phone = substr_replace($phone, ' ', 7, 0);
		$phone = substr_replace($phone, ' ', 10, 0);
		$phone = '+7 ' . $phone;
		return $phone;
		}

	public static function spell($num, $titles)
		{
		$cases = [2, 0, 1, 1, 1, 2];

		return $titles[ ($num % 100 > 4 && $num % 100 < 20) ? 2 : $cases[ min($num % 10, 5) ] ];
		}

	public static function getLegalName($legalEntity)
		{
		if ($legalEntity['legal_entity_type_id'] === 'legal_entity_type_person')
			{
			return $legalEntity['person']['lname'] . ' ' . $legalEntity['person']['fname'] . ' ' . $legalEntity['person']['mname'];
			}
		elseif ($legalEntity['legal_entity_type_id'] === 'legal_entity_type_org')
			{
			return $legalEntity['org']['name'] . ' (ИНН: ' . $legalEntity['org']['inn'] . ')';
			}
		return '';
		}

	public static function fio($person, $notSetText = 'Не указано')
		{
		$fio = trim($person['lname'] . ' ' . $person['fname'] . ' ' . $person['mname']);
		if ($fio === '')
			{
			return $notSetText;
			}
		return $fio;
		}

	public static function fioShort($person, $notSetText = 'Не указано')
		{
		$fio = trim(H::get($person, 'lname', '', '') . ' ' . mb_substr(H::get($person, 'fname', '', ''),
				0,
				1) . '. ' . mb_substr(H::get($person, 'mname', '', ''), 0, 1) . '.');
		if ($fio === '. .')
			{
			return $notSetText;
			}
		return $fio;
		}

	public static function exec($path, $params, $sync = true, $out = '../runtime/consoleOut', $error = '../runtime/consoleError')
		{
		$syncStr = $sync ? '&' : '';
		$json = json_encode($params);
		$json = addcslashes($json, '"');
		$command = "php ../yii $path --json=\"$json\" >> $out 2>> $error $syncStr";
		//		echo $command;
		exec($command);
		}


	public static function implodeWrap($glue, $array, $wrapper)
		{
		$result = [];
		foreach ($array as $key => $value)
			{
			if (is_numeric($key))
				{
				$result[] = $wrapper . $value . $wrapper;
				}
			}
		return implode($glue, $result);
		}

	public static function implodeWith($glue, $array, $callback)
		{
		$clear = [];
		foreach ($array as $value)
			{
			$clear[] = $callback($value);
			}
		return implode($glue, $clear);
		}

	public static function implodeByKey($glue, $array, $key)
		{
		$clear = [];
		foreach ($array as $value)
			{
			$clear[] = self::get($value, $key);
			}
		return implode($glue, $clear);
		}

	public static function implodeByKeyWrap($glue, $array, $key, $wrapper)
		{
		$clear = [];
		foreach ($array as $value)
			{
			$clear[] = $wrapper . self::get($value, $key) . $wrapper;
			}
		return implode($glue, $clear);
		}

	public static function linerizeArray($array, $key, $wrapper = '')
		{
		$result = [];
		foreach ($array as $value)
			{
			$result[] = $wrapper . self::get($value, $key) . $wrapper;
			}
		return $result;
		}

	public static function randomString($length, $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz")
		{
		$text = '';
		for ($i = 0; $i < $length; $i++)
			{
			$text .= $chars[ rand(0, strlen($chars) - 1) ];
			}
		return $text;
		}
	}
