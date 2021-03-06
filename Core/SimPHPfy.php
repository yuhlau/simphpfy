<?php

/* 
 * Created by Hei
 */

/*
 * The base class of SimPHPfy Framework. SimPHPfy class is used for defining
 * class location(namespace) and loading when needed
 */
class SimPHPfy{
    /*
     * Hold a reference to the default DataSource
     * 
     * @var DataSource
     */
    private static $_datasource;

    /*
     * Hold an array of class name and their namespaces. The namespace
     * is actually the location where the class is located
     * 
     * @var Array
     */
    protected static $_classNamespaces = array();

    /*
     * Load a namespace list into the SimPHPfy for tracking. In SimPHPfy, 
     * namespace list are suggested to stored in a namespace file named 
     * namespace.php and is convertionally stored inside the folder with 
     * the same name as the package.
     * 
     * However, SimPHPfy also support blind namespace declaration and search
     * through every file in a directory. This is not recommended though it
     * provides convenience for folders that are actively changing.
     * 
     * @param String $packagePath 
     * 
     * @return void
     */
    public static function package($packagePath){
        /*
         * Try to include the namespace file from the package first becase
         * it is less costly as searching the whole directory
         */
        $namespaceFile = $packagePath . DS . 'namespace.php';
        if (file_exists($namespaceFile)) {
            require $namespaceFile;
        } else {
            // perform the costly directory search
            if (!is_dir($packagePath) || !is_readable($packagePath)) {
                throw new InvalidPackagePathException($packagePath);
            }
            $realpath = realpath($packagePath);
            $dirHandle = opendir($realpath);
            while (false !== ($file = readdir($dirHandle))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                // remove the extendsion .php from the file
                $filename = basename($file, '.php');
                if ($filename == $file){
                    /*
                     * after basename() the value is still the same, the 
                     * file is not a PHP
                     */
                    continue;
                }
                self::definePackage($filename, $packagePath);
            }
        }
    }

    /*
     * The public wrapper function to declares a package for a class
     * 
     * @param String|Array the class name/list of class names to be 
     *  declared.
     * @param String $namespace the namespace where the class is in. 
     *  On the other words, the location where the class is in
     * 
     * @return void
     */
    public static function definePackage($class, $namespace) {
        if (is_array($class)) {
            foreach($class as $key => $name) {
                self::setNamespace($name, $namespace);
            }
        } else {
            self::setNamespace($class, $namespace);
        }
    }

    /*
     * define the namespace of a particular class
     * 
     * @param String $class the name of the class to be defined
     * @param String $namespace the namespace of the class
     */
    public static function setNamespace($class, $namespace) {
        // check if the same class has ben defined before
        if (array_key_exists($class, self::$_classNamespaces)) {
            throw new ClassCollisionException(
            array($class, self::$_classNamespaces[$class])
            );
        }
        self::$_classNamespaces[$class] = $namespace;
    }

    /*
     * load a class file into the system. The loaded class must be already
     * defined in a pakcage using package()
     * 
     * @param String $class the name of the class to load
     * 
     * @return void
     */
    public static function load($class) {
        if (array_key_exists($class, self::$_classNamespaces)) {
            $path = self::$_classNamespaces[$class].$class.".php";
            if (file_exists($path)) {
                require $path;
            } else {
                throw new MissingClassFileException($path);
            }
        } else {
            throw new UnknownClassException($class);
        }
    }

    public static function getDatasource() {
        return self::$_datasource;
    }

    public static function setDatasource($datasource) {
        self::$_datasource = $datasource;
    }

}
