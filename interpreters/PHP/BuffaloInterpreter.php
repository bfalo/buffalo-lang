<?php
/**
* Buffalo Lang Interpreter PHP
*
* Version 0.1
*
* Daniel Givney http://github.com/dgivney
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*
* @link        http://bfalo.com
* @link        http://github.com/dgivney
* @license     http://www.apache.org/licenses/LICENSE-2.0
*/
class BuffaloInterpreter
{

    /**
     * define some constants
     */
    const T_COMMENT = 'T_COMMENT';
    const T_BUFFALO_UCFIRST = 'T_BUFFALO_UCFIRST';
    const T_BUFFALO_LOWER = 'T_BUFFALO_LOWER';
    const T_OUTPUT = 'T_OUTPUT';
    const T_LOOP_BEGIN = 'T_LOOP_BEGIN';
    const T_LOOP_END = 'T_LOOP_END';

    /**
     * _tokens
     *
     * @var array $_tokens associated list of possible tokens and their types
     */
    protected $_tokens = array(
        self::T_BUFFALO_UCFIRST => array(
            'syntax' => 'Buffalo'
        ),
        self::T_BUFFALO_LOWER => array(
            'syntax' => 'buffalo'
        ),
        self::T_OUTPUT => array(
            'syntax' => '.'
        ),
        self::T_LOOP_BEGIN => array(
            'syntax' => '('
        ),
        self::T_LOOP_END => array(
            'syntax' => ')'
        )
    );

    /**
     * _program
     *
     * @var array $_program contains the program being parsed as a char array
     */
    protected $_program = null;

    /**
     * _buffer
     *
     * @var string $_buffer contains the currently active buffer in memory
     */
    protected $_buffer = null;

    /**
     * _size
     *
     * @var integer $_size max number of addressable spaces in memory
     */
    protected $_size = 256;

    /**
     * _memory
     *
     * @var array $_memory contains the programs memory space
     */
    protected $_memory = array();

    /**
     * _pointer
     *
     * @var array $_pointer contains the segment currently pointed to in memory
     */
    protected $_pointer = array();

    /**
     * _flags
     *
     * @var array $_flags contains the system state flags
     */
    protected $_flags = array();

    /**
     * syntax
     *
     * @var array $syntax contains the tokenized syntax tree after interpretation
     */
    public $syntax = null;

    /**
     * debug
     *
     * @var boolean $debug print stats after each iteration
     */
    public $debug = false;

    /**
     * __construct method
     *
     * @param string $program
     * @param boolean $debug
     * @return void
     */
    public function __construct($program = '', $debug = false)
    {
        $this->debug = $debug;
        $this->parse($program);
    }

    /**
     * _resetFlags method
     *
     * @param array $keys
     * @return void
     */
    protected function _resetFlags($keys = array())
    {
        if (!$keys) {
            $keys = array(
                self::T_COMMENT,
                self::T_BUFFALO_UCFIRST,
                self::T_BUFFALO_LOWER,
                self::T_OUTPUT,
                self::T_LOOP_BEGIN,
                self::T_LOOP_END
            );
        }
        $reset = array_fill_keys($keys, 0);
        $this->_flags = array_merge($this->_flags, $reset);
    }

    /**
     * _tokenize method
     *
     * @return void
     */
    protected function _tokenize()
    {
        $bufferLength = strlen($this->_buffer);

        foreach($this->_tokens as $type => $token) {

            $tokenLength = strlen($token['syntax']);

            if ($tokenLength > $bufferLength) {
                continue;
            }

            if (substr_compare($this->_buffer, $token['syntax'], -$tokenLength) === 0) {

                if ($bufferLength > $tokenLength
                    && $comment = trim(substr($this->_buffer, 0, -$tokenLength))) {

                    $this->syntax[] = array(
                        self::T_COMMENT => array(
                            'syntax' => $comment
                        )
                    );
                }

                $this->syntax[] = array(
                    $type => $token
                );
                $this->_buffer = '';

                return;
            }
        }
    }

    /**
     * _interpretAll method
     *
     * @return void
     */
    protected function _interpretAll()
    {
        $this->_pointer = 0;
        $this->_memory = array_fill(0, $this->_size, 0);
        $this->_resetFlags();
        $this->_interpret($this->syntax);
    }

    /**
     * _interpret method
     *
     * @param array $syntax The syntax to be interpreted
     * @param mixed (false|integer) $pointer When branching into a loop
     *    this contains the memory segment to be evaluated at the loop's end
     * @return mixed void|intger When branching returns the index at loops end
     */
    protected function _interpret($syntax = array(), $pointer = false)
    {
        if ($syntax) {

            for($index = 0; $index < count($syntax); $index++) {

                switch(key($syntax[$index])) {

                    # Loop Begin
                    case self::T_LOOP_BEGIN:
                        $index = $index + $this->_interpret(array_slice($syntax, $index + 1), $this->_pointer) + 1;
                        break;

                    # Loop End
                    case self::T_LOOP_END:

                        if ($pointer !== false) {

                            if ($this->_memory[$pointer] === 0) {
                                return $index;
                            }
                            return $this->_interpret($syntax, $pointer);

                        } else {
                            exit('Fatal error: Unexpected ' . self::T_LOOP_END . ' token');
                        }

                    # output
                    case self::T_OUTPUT:
                        echo chr($this->_memory[$this->_pointer]);
                        break;

                    # Buffalo
                    case self::T_BUFFALO_UCFIRST:

                        if ($this->_flags[self::T_BUFFALO_UCFIRST]) {
                            $this->_memory[$this->_pointer] = $this->_memory[$this->_pointer]++ >= 255
                                ? 255 : $this->_memory[$this->_pointer] ;
                            $this->_resetFlags(array(self::T_BUFFALO_UCFIRST, self::T_BUFFALO_LOWER));
                            break;
                        }

                        if ($this->_flags[self::T_BUFFALO_LOWER]) {
                            $this->_pointer = $this->_pointer++ >= (count($this->_memory) - 1)
                                ? count($this->_memory) - 1 : $this->_pointer ;
                            $this->_resetFlags(array(self::T_BUFFALO_UCFIRST, self::T_BUFFALO_LOWER));
                            break;
                        }

                    # buffalo
                    case self::T_BUFFALO_LOWER:

                        if ($this->_flags[self::T_BUFFALO_LOWER]) {
                            $this->_memory[$this->_pointer] = $this->_memory[$this->_pointer]-- <= 0
                                ? 0 : $this->_memory[$this->_pointer] ;
                            $this->_resetFlags(array(self::T_BUFFALO_UCFIRST, self::T_BUFFALO_LOWER));
                            break;
                        }

                        if ($this->_flags[self::T_BUFFALO_UCFIRST]) {
                            $this->_pointer = $this->_pointer-- <= 0
                                ? 0 : $this->_pointer ;
                            $this->_resetFlags(array(self::T_BUFFALO_UCFIRST, self::T_BUFFALO_LOWER));
                            break;
                        }

                    default:
                        $this->_flags[key($syntax[$index])] += 1;
                        break;
                }
            }

            if ($this->debug) {
                echo '<pre>' . print_r($this->_memory, true) . '</pre>';
                echo '<pre>' . print_r($this->_pointer, true) . '</pre>';
                echo '<pre>' . print_r($this->syntax, true) . '</pre>';
            }

        }
    }

    /**
     * parse method
     *
     * @param string $program
     * @return void
     */
    public function parse($program = '')
    {
        $this->syntax = array();
        $this->_program = str_split($program);

        if ($this->_program) {

            foreach($this->_program as $index => $char) {
                $this->_buffer .= $char;
                $this->_tokenize();
            }
        }

        $this->_interpretAll();
    }
}