<?php namespace Zizaco\MongolidLaravel;

/**
 * This class extends the Zizaco\Mongolid\Model, so, in order
 * to understand the ODM implementation make sure to check the
 * base class.
 *
 * The Zizaco\MongolidLaravel\MongoLid simply extends the original
 * and framework agnostic model of MongoLid and implements some
 * validation rules using Laravel validation components.
 *
 * Remember, this package is meant to be used with Laravel while
 * the "zizaco\mongolid" is meant to be used with other frameworks
 * or even without any.
 *
 * @license MIT
 * @author  Zizaco Zizuini <zizaco@gmail.com>
 */
abstract class MongoLid extends \Zizaco\Mongolid\Model
{
    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = null;

    /**
     * Error message bag
     *
     * @var Illuminate\Support\MessageBag
     */
    public $errors;

    /**
     * List of attribute names which should be hashed on save. For
     * example: array('password');
     *
     * @var array
     */
    protected $hashedAttributes = array();

    /**
     * Save the model to the database if it's valid
     *
     * @param $force Force save even if the object is invalid
     * @return bool
     */
    public function save($force = false)
    {

        if ($this->isValid() || $force)
        {
            $this->hashAttributes();
            
            return parent::save();
        }
        else
        {
            return false;
        }
    }

    /**
     * Verify if the model is valid
     *
     * @return bool
     */
    public function isValid()
    {
        /**
         * Return true if there arent validation rules
         */
        if(! is_array(static::$rules) )
            return true;

        /**
         * Get the attributes and the rules to validate then
         */
        $attributes = $this->attributes;
        $rules = static::$rules;

        /**
         * Verify attributes that are hashed and that have not changed
         * those doesn't need to be validated.
         */
        foreach ($this->hashedAttributes as $hashedAttr)
        {
            if(isset($this->original[$hashedAttr]) && $this->$hashedAttr == $this->original[$hashedAttr])
            {
                unset($this->$hashedAttr);
                unset($rules->$hashedAttr);
            }
        }

        /**
         * Creates validator with attributes and the rules of the object
         */
        $validator = \Validator::make(
            $attributes, $rules
        );

        /**
         * Validate and attach errors
         */
        if ($validator->fails())
        {
            $this->errors = $validator->errors();
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Get the contents of errors attribute
     * 
     * @return Illuminate\Support\MessageBag Validation errors
     */
    public function errors()
    {
        if(! $this->errors)
            $this->errors = new \Illuminate\Support\MessageBag;

        return $this->errors;
    }

    /**
     * Sets the database and the cache component of the model
     * If you extend the __construct() method, please don't forget
     * to call parent::__construct()
     */
    public function __construct()
    {
        if ($this->database == 'mongolid')
        {
            $this->database = \Config::get(
                'database.mongodb.default.database', 'mongolid'
            );    
        }
        
        static::$cacheComponent = \App::make('cache');
    }

    protected function hashAttributes()
    {
        foreach ($this->hashedAttributes as $attr)
        {
            if(! isset($this->original[$attr]) || $this->$attr != $this->original[$attr] )
            {
                $this->$attr = static::$app['hash']->make($this->$attr);
            }
        }
    }
}
