<?php
class PathManagerTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        PathManager::reset();
    }

    public function tearDown() {
        JaossRequest::destroyInstance();
    }

    public function testPathsStartsEmptyAndIsArray() {
        $paths = PathManager::getPaths();
        $this->assertInternalType("array", $paths);
        $this->assertEquals(0, count($paths));
    }
    
    public function testPathsCountIsZeroAfterPathReset() {
        $paths = PathManager::getPaths();
        $this->assertInternalType("array", $paths);
        $this->assertEquals(0, count($paths));
    }
    
    public function testPathsCountIsOneAfterPathAddedViaLoadPath() {
        $paths = PathManager::getPaths();
        $this->assertEquals(0, count($paths));
        
        PathManager::loadPath("foo", "bar", "baz", "test");
        
        $paths = PathManager::getPaths();
        $this->assertEquals(1, count($paths));
    }
    
    public function testAddPathsThrowsExceptionWithStringArgument() {
        try {
            PathManager::loadPaths("foo", "bar", "baz", "test");    // loadPaths expects arrays
        } catch (CoreException $e) {
            $this->assertEquals(0, $e->getCode());  //@todo change when this exception gets a code!
            return;
        }
        $this->fail("Expected exception not raised");
    }
    
    public function testAddPathsThrowsExceptionWithEmptyArrayArgument() {
        try {
            PathManager::loadPaths(array());
        } catch (CoreException $e) {
            $this->assertEquals(0, $e->getCode());  //@todo change when this exception gets a code!
            return;
        }
        $this->fail("Expected exception not raised");
    }
    
    public function testPathsCountIsOneAfterPathAddedViaLoadPaths() {
        $paths = PathManager::getPaths();
        $this->assertEquals(0, count($paths));
        
        PathManager::loadPaths(array("foo", "bar", "baz", "test"));
        
        $paths = PathManager::getPaths();
        $this->assertEquals(1, count($paths));
    }

    public function testMatchUrlWithNoPathsLoaded() {
        $this->setExpectedException("CoreException");
        PathManager::matchUrl("/");
    }

    public function testMatchUrlWithPathsLoadedButNoMatch() {
        PathManager::loadPath("foo", "bar", "baz", "test");
        try {
            PathManager::matchUrl("/bad/url");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::URL_NOT_FOUND, $e->getCode());
            return;
        }
        $this->fail("Exception not raised");
    }

    public function testLoadPathsSupportsNameArgument() {
        PathManager::loadPaths(array(
            "/foo", "bar", "baz", "test", null, null, "my_path",
        ));
        $path = PathManager::matchUrl("/foo");

        $this->assertEquals("my_path", $path->getName());
    }

    public function testLoadPathSupportsNameArgument() {
        PathManager::loadPath("/foo", "bar", "baz", "test", null, null, "my_path");

        $path = PathManager::matchUrl("/foo");
        $this->assertEquals("my_path", $path->getName());
    }
    
    public function testLoadPathThrowsExceptionWithDuplicateName() {
        PathManager::loadPath("/foo", "bar", "baz", "test", null, null, "my_path");
        try {
            PathManager::loadPath("/foobar", "barfoo", "baztest", "test", null, null, "my_path");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::DUPLICATE_PATH_NAME, $e->getCode());
            return;
        }
        $this->fail("Expected exception not raised");
    }

    public function testLoadPathsSupportsAssociativeNameArgument() {
        PathManager::loadPaths(array(
            "pattern" => "/foo",
            "action" => "bar",
            "controller" => "baz",
            "location" => "test",
            "name" => "my_path",
        ));

        $path = PathManager::matchUrl("/foo");
        $this->assertEquals("my_path", $path->getName());
    }

    public function testMatchUrlWithPathsLoadedAndWithMatch() {
        PathManager::loadPath("^/foo$", "bar", "baz", "test");
        $path = PathManager::matchUrl("/foo");
        $this->assertEquals("^/foo$", $path->getPattern());
        $this->assertEquals("bar", $path->getAction());
        $this->assertEquals("baz", $path->getController());
        $this->assertEquals("test:baz:bar", $path->getName());
        $this->assertFalse($path->isCacheable());
    }

    public function testMatchUrlWithSimplePathsLoadedAndWithMatch() {
        PathManager::loadPath("/foo", "bar", "baz", "test");
        $path = PathManager::matchUrl("/foo");
        $this->assertEquals("^/foo$", $path->getPattern());
        $this->assertEquals("bar", $path->getAction());
        $this->assertEquals("baz", $path->getController());
        $this->assertEquals("test:baz:bar", $path->getName());
        $this->assertFalse($path->isCacheable());
    }

    public function testMatchUrlWithSimpleAssociativeLoadPathsMethod() {
        PathManager::loadPaths(array(
            "pattern" => "/foo",
            "action" => "bar",
            "controller" => "baz",
            "location" => "test",
        ));
        $path = PathManager::matchUrl("/foo");
        $this->assertEquals("^/foo$", $path->getPattern());
        $this->assertEquals("bar", $path->getAction());
        $this->assertEquals("baz", $path->getController());
        $this->assertEquals("test:baz:bar", $path->getName());
        $this->assertFalse($path->isCacheable());
    }

    public function testMatchUrlFailsWithIncorretRequestMethod() {
        $request = JaossRequest::getInstance();
        PathManager::loadPath("/foo", "bar", "baz", "test", "POST");
        try {
            $path = PathManager::matchUrl("/foo");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::URL_NOT_FOUND, $e->getCode());
            return;
        }
        $this->fail("expected exception not raised");
    }

    public function testMatchUrlFailsWithIncorretRequestMethodLowerCase() {
        $request = JaossRequest::getInstance();
        PathManager::loadPath("/foo", "bar", "baz", "test", "post");
        try {
            $path = PathManager::matchUrl("/foo");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::URL_NOT_FOUND, $e->getCode());
            return;
        }
        $this->fail("expected exception not raised");
    }

    public function testMatchUrlFailsWhenExpectedMethodIsGetButMethodIsPost() {
        $request = JaossRequest::getInstance();
        $request->setMethod("POST");
        PathManager::loadPath("/foo", "bar", "baz", "test", "GET");
        try {
            $path = PathManager::matchUrl("/foo");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::URL_NOT_FOUND, $e->getCode());
            return;
        }
        $this->fail("expected exception not raised");
    }

    public function testMatchUrlSucceedsWhenExpectedMethodIsArrayContainingGetAndPost() {
        $request = JaossRequest::getInstance();
        PathManager::loadPath("/foo", "bar", "baz", "test", array("GET", "POST"));
        $path = PathManager::matchUrl("/foo");
        $this->assertEquals(array("GET", "POST"), $path->getRequestMethods());
    }

    public function testMatchUrlSucceedsWhenExpectedMethodIsAll() {
        $request = JaossRequest::getInstance();
        $request->setMethod("POST");

        PathManager::loadPath("/foo", "bar", "baz", "test", "all");
        $path = PathManager::matchUrl("/foo");
        $this->assertEquals(array("ALL"), $path->getRequestMethods());
    }

    public function testMatchUrlSucceedsWhenExpectedMethodSetByLoadPathsAssociative() {
        $request = JaossRequest::getInstance();
        $request->setMethod("POST");

        PathManager::loadPaths(
            array(
                "pattern" => "/foo",
                "action" => "foo",
                "controller" => "foo",
                "location" => "foo",
                "method" => "post",
            )
        );
        $path = PathManager::matchUrl("/foo");
        $this->assertEquals(array("POST"), $path->getRequestMethods());
    }

    public function testsetPrefix() {
        PathManager::setPrefix("/someprefix");
        PathManager::loadPath("/foo", "bar", "baz", "test");
        $path = PathManager::matchUrl("/someprefix/foo");
        $this->assertEquals("^/someprefix/foo$", $path->getPattern());

        try {
            PathManager::matchUrl("/foo");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::URL_NOT_FOUND, $e->getCode());
            return;
        }
        $this->fail("expected exception not raised");
    }

    public function testClearPrefix() {
        PathManager::setPrefix("/someprefix");
        PathManager::loadPath("/foo", "bar", "baz", "test");
        $path = PathManager::matchUrl("/someprefix/foo");
        $this->assertEquals("^/someprefix/foo$", $path->getPattern());
        
        PathManager::clearPrefix();
        PathManager::loadPath("/foo", "barbar", "baz", "test");
        $path = PathManager::matchUrl("/foo");
        $this->assertEquals("^/foo$", $path->getPattern());
    }
    
    public function testGetPathForEmptyOptionsArray() {
        $options = array();

        try {
            $path = PathManager::getPathForOptions($options);
        } catch (CoreException $e) {
            $this->assertEquals(11, $e->getCode());
            return;
        }
        $this->fail("Expected Exception Not Raised");
    }

    public function testGetPathForOptionsWithNoMatch() {
        $options = array(
            "controller" => "Foo",
            "app" => "Foo",
            "action" => "index",
        );

        try {
            $path = PathManager::getPathForOptions($options);
        } catch (CoreException $e) {
            $this->assertEquals(11, $e->getCode());
            return;
        }
        $this->fail("Expected Exception Not Raised");
    }

    public function testGetPathForOptionsWithMatch() {
        $options = array(
            "controller" => "Foo",
            "app" => "FooApp",
            "action" => "index",
        );

        PathManager::loadPath("/foo", "index", "Foo", "FooApp");

        $path = PathManager::getPathForOptions($options);

        $this->assertEquals("/foo", $path->getPattern());
        $this->assertEquals("index", $path->getAction());
        $this->assertEquals("Foo", $path->getController());
        $this->assertEquals("FooApp", $path->getApp());
        $this->assertEquals("FooApp:Foo:index", $path->getName());
    }

    public function testGetUrlForOptionsWithArgumentAndMatch() {
        $options = array(
            "controller" => "Foo",
            "app" => "FooApp",
            "action" => "index",
            "id" => "42",
        );

        PathManager::loadPath("/foo/(?P<id>\d+)", "index", "Foo", "FooApp");

        $url = PathManager::getUrlForOptions($options);
        
        $this->assertEquals("/foo/42", $url);
    }

    public function testGetUrlForOptionsWithArgumentAndNoMatch() {
        $options = array(
            "controller" => "Foo",
            "app" => "FooApp",
            "action" => "index",
            "notFound" => "1234",
        );

        PathManager::loadPath("/foo/(?P<id>\d+)", "index", "Foo", "FooApp");

        try {
            $url = PathManager::getUrlForOptions($options);
        } catch (CoreException $e) {
            $this->assertEquals(0, $e->getCode());
            return;
        }
        $this->fail("Expected exception not raised");
    }

    public function testGetPathForNameWithNoMatch() {
        try {
            $path = PathManager::getPathForName("my_path");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::PATH_NAME_NOT_FOUND, $e->getCode());
            return;
        }
        $this->fail("Expected Exception Not Raised");
    }

    public function testGetPathForNameWithInvalidName() {
        try {
            $path = PathManager::getPathForName("");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::PATH_NAME_NOT_VALID, $e->getCode());
            return;
        }
        $this->fail("Expected Exception Not Raised");
    }

    public function testGetPathForNameWithMatch() {
        PathManager::loadPath("/foo", "index", "Foo", "FooApp", null, null, "my_path");

        $path = PathManager::getPathForName("my_path");

        $this->assertEquals("/foo", $path->getPattern());
        $this->assertEquals("index", $path->getAction());
        $this->assertEquals("Foo", $path->getController());
        $this->assertEquals("FooApp", $path->getApp());
        $this->assertEquals("my_path", $path->getName());
    }

    public function testGetPathForNameWithMatchForDefault() {
        PathManager::loadPath("/foo", "my_route", "Foo", "FooApp");

        $path = PathManager::getPathForName("FooApp:Foo:my_route");

        $this->assertEquals("/foo", $path->getPattern());
        $this->assertEquals("my_route", $path->getAction());
        $this->assertEquals("Foo", $path->getController());
        $this->assertEquals("FooApp", $path->getApp());
        $this->assertEquals("FooApp:Foo:my_route", $path->getName());
    }

    public function testGetPathForNameWithPartialMatchAddsTitleCasedControllerArgument() {
        PathManager::loadPath("/bar", "action", "App", "app");

        $path = PathManager::getPathForName("app:action");

        $this->assertEquals("/bar", $path->getPattern());
        $this->assertEquals("action", $path->getAction());
        $this->assertEquals("App", $path->getController());
        $this->assertEquals("app", $path->getApp());
        $this->assertEquals("app:App:action", $path->getName());
    }

    public function testMatchUrlIgnoresDiscardedPaths() {
        PathManager::loadPath("/foo", "index", "Foo", "FooApp");
        PathManager::loadPath("/bar", "bar", "Foo", "FooApp");

        try {
            PathManager::matchUrl("/nomatch");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::URL_NOT_FOUND, $e->getCode());
            // this isn't a valid use case for discarded paths, but it's a valid simulation
            // in reality, a path is only discarded by the request object when a
            // particular exception is thrown, but to simulate the equivalent, let's just match
            // a *valid* path and make sure it doesn't work
            try {
                PathManager::matchUrl("/foo");
            } catch (CoreException $e) {
                $this->assertEquals(CoreException::URL_NOT_FOUND, $e->getCode());
                return;
            }
        }
        $this->fail("Expected exception not raised");
    }

    public function testReloadPathsMarksPathsNotDiscarded() {
        PathManager::loadPath("/foo", "index", "Foo", "FooApp");

        try {
            PathManager::matchUrl("/nomatch");
        } catch (CoreException $e) {
            $this->assertEquals(CoreException::URL_NOT_FOUND, $e->getCode());
            $paths = PathManager::getPaths();
            
            $path = reset($paths);

            $this->assertTrue($path->isDiscarded());
            PathManager::reloadPaths();
            $path = PathManager::matchUrl("/foo");
            $this->assertFalse($path->isDiscarded());
        }
    }

    public function testGetPathsToArray() {
        PathManager::loadPaths(array("foo", "bar", "baz", "test"));

        $paths = PathManager::getPathsToArray();

        $this->assertEquals(array(
            "test:baz:bar" => array(
                "pattern" => "foo",
                "action" => "bar",
                "controller" => "baz",
                "app" => "test",
                "name" => "test:baz:bar",
                "cacheTtl" => null,
                "requestMethods" => array(),
            ),
        ), $paths);
    }

    public function testSetPathsFromArray() {
        PathManager::setPathsFromArray(array(
            "test:baz:bar" => array(
                "pattern" => "foo",
                "action" => "bar",
                "controller" => "baz",
                "app" => "test",
                "name" => "test:baz:bar",
                "cacheTtl" => null,
                "requestMethods" => array(),
            ),
        ));

        $paths = PathManager::getPaths();

        $this->assertEquals(1, count($paths));

        $path = $paths["test:baz:bar"];

        $this->assertEquals("foo", $path->getPattern());
        $this->assertEquals("bar", $path->getAction());
        $this->assertEquals("baz", $path->getController());
        $this->assertEquals("test", $path->getApp());
        $this->assertEquals("test:baz:bar", $path->getName());
        $this->assertEquals(null, $path->getCacheTtl());
        $this->assertEquals(array(), $path->getRequestMethods());
    }
}
