<?php

namespace PHPixie\Tests\Router\Routes\Route\Pattern;

/**
 * @coversDefaultClass \PHPixie\Router\Routes\Route\Pattern\Prefix
 */
abstract class PrefixTest extends \PHPixie\Tests\Router\Routes\Route\PatternTest
{
    /**
     * @covers ::match
     * @covers ::<protected>
     */
    public function testMatch()
    {
        $this->matchTest(false);
        $this->matchTest(true, false);
        $this->matchTest(true, true, false);
        $this->matchTest(true, true, true);
        $this->matchTest(true, true, true, true);
        $this->matchTest(null, null, null, true);
    }
    
    /**
     * @covers ::generate
     * @covers ::<protected>
     */
    public function testGenerate()
    {
        $this->generateTest();
        $this->generateTest(true);
        $this->generateTest(true, true);
        $this->generateTest(true, true, true);
    }
    
    protected function matchTest(
        $methodValid = null,
        $hostValid = null,
        $pathValid = null,
        $groupValid = false
    )
    {
        $this->route = $this->routeMock(array('group'));
        $fragment = $this->getFragment();
        $match = $this->prepareMatchTest($fragment, $methodValid, $hostValid, $pathValid, $groupValid);
        $this->assertSame($match, $this->route->match($fragment));
    }
    
    protected function prepareMatchTest($fragment, $methodValid, $hostValid, $pathValid, $groupValid)
    {
        $builderAt  = 0;
        $configAt   = 0;
        $fragmentAt = 0;
        $matcherAt  = 0;
        
        $this->prepareIsMethodValid($fragment, $methodValid, $configAt, $fragmentAt);
        if($methodValid === false) {
            return null;
        }
        
        $this->method($fragment, 'host', 'pixie', array(), $fragmentAt++);
        list($hostAttributes, $host) = $this->prepareMatchPattern(
            'host',
            $hostValid,
            'pixie',
            true,
            $configAt,
            $builderAt,
            $matcherAt
        );
        
        if($hostAttributes === null) {
            return null;
        }
        
        $this->method($fragment, 'path', 'pixie', array(), $fragmentAt++);
        list($pathAttributes, $path) = $this->prepareMatchPattern(
            'path',
            $pathValid,
            'pixie',
            $hostValid === null,
            $configAt,
            $builderAt,
            $matcherAt
        );
        
        if($pathAttributes === null) {
            return null;
        }
        
        $defaults = array('default' => 1, 'override' => 'defaults');
        $this->prepareConfigGet('defaults', $defaults, array(), $configAt);
        
        $attributes = array_merge($defaults, $hostAttributes, $pathAttributes);
        
        $subFragment = $this->getFragment();
        $this->method($fragment, 'copy', $subFragment, array($path, $host), $fragmentAt++);
        
        $group = $this->getRoute();
        $this->method($this->route, 'group', $group, array());
        
        $match = $groupValid ? $this->getMatch() : null;
        $this->method($group, 'match', $match, array($subFragment), 0);
        
        if($groupValid) {
            $this->method($match, 'prependAttributes', null, array($attributes), 0);
        }
        
        return $match;
    }
    
    protected function prepareMatchPattern(
        $name,
        $isValid,
        $string,
        $prepareAttributePatterns,
        &$configAt,
        &$builderAt,
        &$matcherAt
    )
    {
        $pattern = $this->preparePattern(
            $name,
            $isValid !== null,
            $prepareAttributePatterns,
            $configAt,
            $builderAt
        );
        
        if($pattern === null) {
            return array(array(), $string);
        }
        
        $attributes = $isValid ? array($name => 1, 'override' => $name) : null;
        $this->method($this->builder, 'matcher', $this->matcher, array(), $builderAt++);
        
        $result = array($attributes, 'tail-'.$name);
        $this->method($this->matcher, 'matchPrefix', $result, array($pattern, $string), $matcherAt++);
        return $result;
    }
    
    protected function generateTest($withHost = false, $pathExists = false, $hostExists = false)
    {
        $this->route = $this->routeMock(array('group'));
        $match = $this->getMatch();
        
        $builderAt  = 0;
        $configAt   = 0;
        $fragmentAt = 0;
        
        $group = $this->getRoute();
        $this->method($this->route, 'group', $group, array());
        
        $fragment = $this->getFragment();
        $this->method($group, 'generate', $fragment, array($match, $withHost), 0);
        
        $attributes = array('a' => 2);
        $mergedAttributes = $this->prepareMergeAttributes($match, $attributes, $configAt);
        
        $path = $this->prepareGeneratePatternString(
            'path',
            $pathExists,
            $mergedAttributes,
            true,
            $configAt,
            $builderAt
        );
        
        $this->method($fragment, 'path', 'pixie', array(), $fragmentAt++);
        $this->method($fragment, 'setPath', null, array($path.'pixie'), $fragmentAt++);
        
        if($withHost) {
            $host = $this->prepareGeneratePatternString(
                'host',
                $hostExists,
                $mergedAttributes,
                !$pathExists,
                $configAt,
                $builderAt
            );
            
        
            $this->method($fragment, 'host', 'pixie', array(), $fragmentAt++);
            $this->method($fragment, 'setHost', null, array($host.'pixie'), $fragmentAt++);
        }else{
            $host = null;
        }
        
        if($withHost) {
            $result = $this->route->generate($match, true);
            
        }else{
            $result = $this->route->generate($match);
        }
        
        $this->assertSame($fragment, $result);
    }
    
    protected function getRoute()
    {
        return $this->quickMock('\PHPixie\Router\Routes\Route');
    }
    
    abstract protected function routeMock($methods);
}