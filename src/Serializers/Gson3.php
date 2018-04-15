<?php

namespace Brightzone\GremlinDriver\Serializers;

use Brightzone\GremlinDriver\InternalException;

/**
 * Gremlin-server PHP JSON Serializer class
 * Builds and parses message body for Messages class
 *
 * @category DB
 * @package  Serializers
 * @author   Dylan Millikin <dylan.millikin@brightzone.fr>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 apache2
 */
class Gson3 implements SerializerInterface
{
    /**
     * @var string the name of the serializer
     */
    public static $name = 'GRAPHSON3';

    /**
     * @var int Value of this serializer. Will be deprecated in TP3
     */
    public static $mimeType = 'application/json';

    /**
     * @var array The native supported types that the serializer can convert to graphson
     */
    protected static $supportedFromTypes = [
        "string",
        "boolean",
        "double",
        "integer",
        "array",
        "object",
    ];

    /**
     * @var array The GraphSON supported types that the serializer can deconvert from
     */
    protected static $supportedGSTypes = [
        "g:Int32",
        "g:Int64",
        "g:Date",
        "g:Timestamp",
        "g:UUID",
        "g:Float",
        "g:Double",
        "g:List",
        "g:Map",
        "g:Set",
        "g:Class",
        "g:Path",
        "g:Tree",
        "g:Vertex",
        "g:VertexProperty",
        "tinker:graph",
        "g:Edge",
        "g:Property",
    ];

    /**
     * Serializes the data
     *
     * @param array &$data data to be serialized
     *
     * @return int length of generated string
     */
    public function serialize(&$data)
    {
        //convert the array into the correct format
        $data = $this->convert($data);
        $jsonEncoder = new Json;

        return $jsonEncoder->serialize($data);
    }

    /**
     * Unserializes the data
     *
     * @param array $data data to be unserialized
     *
     * @return array unserialized message
     */
    public function unserialize($data)
    {
        $jsonEncoder = new Json;
        $data = $jsonEncoder->unserialize($data);

        return $this->deconvert($data);
    }

    /**
     * Get this serializer's Name
     *
     * @return string name of serializer
     */
    public function getName()
    {
        return self::$name;
    }

    /**
     * Get this serializer's value
     * This will be deprecated with TP3 Gremlin-server
     *
     * @return string name of serializer
     */
    public function getMimeType()
    {
        return self::$mimeType;
    }

    /**
     * Transforms a variable into it's graphson 3.0 counterpart structure
     *
     * @param mixed $item the variable to convert to the graphson 3 structure
     *
     * @return array|string The same element in it's new form.
     * @throws InternalException
     */
    public function convert($item)
    {
        $converted = [];
        $type = gettype($item);
        if(in_array($type, self::$supportedFromTypes))
        {
            //use the type name to run the proper method
            $method = "convert" . ucfirst($type);
            $converted = $this->$method($item);
        }
        else
        {
            throw new InternalException("Item type '{$type}' is not currently supported by the serializer (" . __CLASS__ . ")", 500);
        }

        return $converted;
    }

    /**
     * Convert a string into it's graphson 3.0 form
     *
     * @param string $string The string to convert
     *
     * @return string converted string (same as original currently)
     */
    public function convertString($string)
    {
        return $string;
    }

    /**
     * Convert an integer into it's graphson 3.0 form
     *
     * @param int $int The integer to convert
     *
     * @return array converted integer
     */
    public function convertInteger($int)
    {
        $intSize = [
            4 => "g:Int32",
            8 => "g:Int64",
        ];

        return [
            "@type"  => $intSize[PHP_INT_SIZE],
            "@value" => $int,
        ];
    }

    /**
     * Convert an float/double into it's graphson 3.0 form
     *
     * @param double $double The float/double to convert
     *
     * @return array converted double
     */
    public function convertDouble($double)
    {
        return [
            "@type"  => "g:Double",
            "@value" => $double,
        ];
    }

    /**
     * Convert a boolean into it's graphson 3.0 form
     *
     * @param bool $bool The boolean to convert
     *
     * @return bool converted boolean (same as original)
     */
    public function convertBoolean($bool)
    {
        return $bool;
    }

    /**
     * Convert an array into it's corresponding graphson 3.0 form(List or Map)
     * This differentiates between Maps and Lists (we do not convert to Set)
     *
     * @param array $array The array to convert
     *
     * @return array converted array
     */
    public function convertArray($array)
    {
        $isList = (empty($array) || array_keys($array) === range(0, count($array) - 1));

        return ($isList ? $this->convertList($array) : $this->convertMap($array));
    }

    /**
     * Convert an array into a graphson 3.0 List
     *
     * @param array $array The array to convert
     *
     * @return array converted to GS3 List
     */
    public function convertList($array)
    {
        $converted = [
            "@type"  => "g:List",
            "@value" => [],
        ];

        foreach($array as $key => $value)
        {
            $converted["@value"][] = $this->convert($value);
        }

        return $converted;
    }

    /**
     * Convert an array into a graphson 3.0 Map
     *
     * @param array $array The array to convert
     *
     * @return array converted to GS3 Map
     */
    public function convertMap($array)
    {
        $converted = [
            "@type"  => "g:Map",
            "@value" => [],
        ];
        foreach($array as $key => $value)
        {
            $converted["@value"][] = $this->convert($key);
            $converted["@value"][] = $this->convert($value);
        }

        return $converted;
    }

    /**
     * Convert an object into a graphson 3.0 Map
     * Currently unsuported
     *
     * @param object $object The array to convert
     *
     * @throws InternalException
     */
    public function convertObject($object)
    {
        throw new InternalException("Objects aren't currently supported by the " . self::$name . " serializer (" . __CLASS__ . "). Error produced by: " . get_class($object), 500);
    }

    /**
     * Transforms a graphson 3.0 "variable" into it's native structure
     *
     * @param mixed $item the variable to convert to php native
     *
     * @return mixed The same element in it's new form.
     * @throws InternalException
     */
    public function deconvert($item)
    {
        $deconverted = [];

        if(is_array($item) && isset($item["@type"]) && in_array($item["@type"], self::$supportedGSTypes))
        {
            //type exists in array and is found in our supported types
            $method = "deconvert" . ucfirst(str_replace(["g:", "gx:", ":"], "", $item["@type"]));
            $deconverted = $this->$method($item["@value"]);
        }
        elseif(is_array($item) && isset($item["@type"]) && !in_array($item["@type"], self::$supportedGSTypes))
        {
            //type exists in array but is not currently supported
            throw new InternalException("Item type '{$item["@type"]}' is not currently supported by the serializer (" . __CLASS__ . ")", 500);
        }
        elseif(is_array($item) && !isset($item["@type"]))
        {
            //regular array, just pass it along
            foreach($item as $key => $value)
            {
                $deconverted[$key] = $this->deconvert($value);
            }
        }
        else
        {
            //regular variable, just pass it along.
            $deconverted = $item;
        }

        return $deconverted;
    }

    /**
     * Deconvert an Int32 into it's native form
     *
     * @param int $int The int to convert
     *
     * @return int deconverted Int32
     */
    public function deconvertInt32($int)
    {
        return $int;
    }

    /**
     * Deconvert an Int64 into it's native form
     *
     * @param int $int The int to convert
     *
     * @return int deconverted Int64
     * @throws InternalException
     */
    public function deconvertInt64($int)
    {
        if(PHP_INT_SIZE == 4)
        {
            throw new InternalException("You are running a 32bit PHP and cannot convert the 64bit Int provided in the GraphSON 3.0");
        }

        return $int;
    }

    /**
     * Deconvert a Double into it's native form
     *
     * @param double $double The double to convert
     *
     * @return double deconverted Double
     */
    public function deconvertDouble($double)
    {
        return $double;
    }

    /**
     * Deconvert a Float into it's native form
     *
     * @param double $float The float to convert
     *
     * @return double deconverted Float
     */
    public function deconvertFloat($float)
    {
        return $this->deconvertDouble($float);
    }

    /**
     * Deconvert a Timestamp into it's native form
     *
     * @param int $timestamp The Timestamp to convert
     *
     * @return int deconverted Timestamp
     */
    public function deconvertTimestamp($timestamp)
    {
        return $this->deconvertInt32($timestamp);
    }

    /**
     * Deconvert a Date into it's native form
     *
     * @param int $date The date to convert
     *
     * @return int deconverted Date
     */
    public function deconvertDate($date)
    {
        return $this->deconvertInt32($date);
    }

    /**
     * Deconvert a UUID into it's native form
     *
     * @param string $uuid The UUID to convert
     *
     * @return string deconverted UUID
     */
    public function deconvertUUID($uuid)
    {
        return $uuid;
    }

    /**
     * Deconvert a List into it's native form (Array)
     *
     * @param array $list The List to convert
     *
     * @return array deconverted List
     */
    public function deconvertList($list)
    {
        $deconverted = [];
        foreach($list as $value)
        {
            $deconverted[] = $this->deconvert($value);
        }

        return $deconverted;
    }

    /**
     * Deconvert a Set into it's native form (Array)
     *
     * @param array $set The Set to convert
     *
     * @return array deconverted Set
     */
    public function deconvertSet($set)
    {
        return $this->deconvertList($set);
    }

    /**
     * Deconvert a Map into it's native form (Array)
     *
     * @param array $map The Map to convert
     *
     * @return array deconverted Map
     * @throws InternalException
     */
    public function deconvertMap($map)
    {
        $deconverted = [];

        if(count($map) % 2 != 0)
        {
            throw new InternalException("Failed to deconvert Map item from graphson 3.0. Odd number of elements found (should be even)", 500);
        }

        while(0 < count($map))
        {
            $key = $this->deconvert(array_shift($map));
            $value = $this->deconvert(array_shift($map));

            if(!is_numeric($key) && !is_string($key))
            {
                throw new InternalException("Failed to deconvert Map item from graphson 3.0. A key was of type [" . gettype($key) . "], only Integers and Strings are supported", 500);
            }
            $deconverted[$key] = $value;
        }

        return $deconverted;
    }

    /**
     * Deconvert a Property into it's native form
     *
     * @param array $prop The Property to convert
     *
     * @return mixed deconverted Property
     */
    public function deconvertProperty($prop)
    {
        return $this->deconvert($prop);
    }

    /**
     * Deconvert a VertexProperty into it's native form
     *
     * @param array $vertexProp The VertexProperty to convert
     *
     * @return mixed deconverted VertexProperty
     */
    public function deconvertVertexProperty($vertexProp)
    {
        return $this->deconvert($vertexProp);
    }

    /**
     * Deconvert a Path into it's native form
     *
     * @param array $path The Path to convert
     *
     * @return array deconverted Path
     */
    public function deconvertPath($path)
    {
        return $this->deconvert($path);
    }

    /**
     * Deconvert a TinkerGraph into it's native form
     *
     * @param array $tinkergraph The TinkerGraph to convert
     *
     * @return array deconverted TinkerGraph
     */
    public function deconvertTinkergraph($tinkergraph)
    {
        return $this->deconvert($tinkergraph);
    }

    /**
     * Deconvert a Tree into it's native form
     *
     * @param array $tree The Tree to convert
     *
     * @return array deconverted Tree
     */
    public function deconvertTree($tree)
    {
        return $this->deconvert($tree);
    }

    /**
     * Deconvert a Class into it's native form
     *
     * @param string $classname The class to convert
     *
     * @return void
     * @throws InternalException
     */
    public function deconvertClass($classname)
    {
        throw new InternalException("The GraphSON 3.0 contained a Class element ({$classname}). Classes are not currently supported");
    }

    /**
     * Deconvert a Vertex into it's native form
     *
     * @param array $vertex The Vertex to convert
     *
     * @return array deconverted Vertex
     */
    public function deconvertVertex($vertex)
    {
        $vertex["type"] = "vertex";

        return $this->deconvert($vertex);
    }

    /**
     * Deconvert a Edge into it's native form
     *
     * @param array $edge The Edge to convert
     *
     * @return array deconverted Edge
     */
    public function deconvertEdge($edge)
    {
        $edge["type"] = "edge";

        return $this->deconvert($edge);
    }
}
