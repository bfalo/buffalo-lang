/*
* Buffalo Lang Interpreter jQuery
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
(function($){

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
    var tokens = [
        {T_BUFFALO_UCFIRST: {'syntax': 'Buffalo'}},
        {T_BUFFALO_LOWER: {'syntax': 'buffalo'}},
        {T_OUTPUT: {'syntax': '.'}},
        {T_LOOP_BEGIN: {'syntax': '('}},
        {T_LOOP_END: {'syntax': ')'}}
    ];

    // provide default settings
    var defaultSettings = {
        debug: false,                   // debug flag
        memorySize: 256,                // integer Size of memory buffer
        maxRecursion: 2500,             // integer Max amount of recursion
        outputSelector: '#output',      // selector to return output too
    };

    $.fn.buffalo = function(settings) {

        if (typeof option === 'object') {
            settings = option;
        } else if (typeof option == 'string') {
            var data = this.data('_buffalo');

            if (data) {

                if (defaultSettings[option] !== undefined) {

                    if (settings !== undefined) {
                        return true;
                    } else {
                        return data.settings[option];
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        settings = $.extend({}, defaultSettings, settings || {});

        return this.each(function() {

            var $settings = jQuery.extend(true, {}, settings);
            $settings.el = $(this);

            var buffalo = new Buffalo($settings);

            buffalo.generate();
            buffalo.parse();

            $settings.el.data('_buffalo', buffalo);
        });
    };

    function Buffalo(settings) {
        this.buffalo = null;
        this.settings = settings;
        this.program = '';
        this.output = '';
        this.flags = {};
        this.pointer = 0;
        this.memory = [];
        this.buffer = [];
        this.syntax = [];

        return this;
    }

    Buffalo.prototype = {

        generate: function() {

            var $this = this;

            if ($this.buffalo) { return $this.buffalo; }

            // code
        },

        _resetFlags: function(keys) {

            var $this = this;

            if (!keys) {
                keys = [
                    T_COMMENT,
                    T_BUFFALO_UCFIRST,
                    T_BUFFALO_LOWER,
                    T_OUTPUT,
                    T_LOOP_BEGIN,
                    T_LOOP_END,
                ];
            }

            $.each(keys, function(key, value) {
                $this.flags[value] = false;
            });
        },

        _interpretAll: function() {

            var $this = this;

            $this.pointer = 0;
            $this.memory = Array.apply(null, new Array($this.settings.memorySize))
                .map(Number.prototype.valueOf, 0);
            $this._resetFlags();
            console.log($this.flags);
            $this._interpret($this.syntax);
            $($this.settings.outputSelector).val($this.output);
        },

        _interpret: function(syntax, pointer) {

            var $this = this;
            pointer = typeof pointer !== 'undefined' ? pointer : false;

            if (syntax) {

                for(index = 0; index < syntax.length; index++) {

                    switch(Object.keys(syntax[index])[0]) {

                        case T_LOOP_BEGIN:
                            index = index + $this._interpret(syntax.slice(index + 1), $this.pointer) + 1;
                            break;

                        case T_LOOP_END:

                            if (pointer !== false) {

                                if ($this.memory[pointer] === 0) {
                                    return index;
                                }
                                return $this._interpret(syntax, pointer);

                            } else {
                                console.log('Fatal error: Unexpected ' + T_LOOP_END + ' token');
                                return false;
                            }

                        case T_OUTPUT:

                            $this.output += String.fromCharCode($this.memory[$this.pointer]);
                            break;

                        case T_BUFFALO_UCFIRST:

                            if ($this.flags[T_BUFFALO_UCFIRST]) {
                                $this.memory[$this.pointer] = ($this.memory[$this.pointer]++ >= 255)
                                    ? 255 : $this.memory[$this.pointer] ;
                                $this._resetFlags([T_BUFFALO_UCFIRST, T_BUFFALO_LOWER]);
                                break;
                            }

                            if ($this.flags[T_BUFFALO_LOWER]) {
                                $this.pointer = ($this.pointer++ >= $this.memory.length - 1)
                                    ? $this.memory.length - 1 : $this.pointer ;
                                $this._resetFlags([T_BUFFALO_UCFIRST, T_BUFFALO_LOWER]);
                                break;
                            }

                        case T_BUFFALO_LOWER:

                            if ($this.flags[T_BUFFALO_LOWER]) {
                                $this.memory[$this.pointer] = ($this.memory[$this.pointer]-- <= 0)
                                    ? 0 : $this.memory[$this.pointer] ;
                                $this._resetFlags([T_BUFFALO_UCFIRST, T_BUFFALO_LOWER]);
                                break;
                            }

                            if ($this.flags[T_BUFFALO_UCFIRST]) {
                                $this.pointer = ($this.pointer-- <= 0)
                                    ? 0 : $this.pointer ;
                                $this._resetFlags([T_BUFFALO_UCFIRST, T_BUFFALO_LOWER]);
                                break;
                            }

                        default:
                            $this.flags[Object.keys(syntax[index])[0]] += 1;
                            break;
                    }

                };
            }

            if ($this.settings.debug) {
                console.log('Memory: ' + $this.memory);
                console.log('Pointer: ' + $this.pointer);
                console.log('Output: ' + $this.output);
            }

        },

        _tokenize: function() {

            var $this = this;

            $.map(tokens, function(item, key) {

                for (var index in item) {

                    var token = item[index].syntax;

                    if (token.length > $this.buffer.length) {
                        return null;
                    }

                    if ($this.buffer.indexOf(token, $this.buffer.length - token.length) !== -1) {

                        var comment = $this.buffer.substring(0, $this.buffer.length - token.length).trim();

                        if ($this.buffer.length > token.length
                            && comment) {

                            $this.syntax.push({T_COMMENT: {'syntax': comment}});
                        }

                        $this.syntax.push(item);
                        $this.buffer = '';

                        return false;
                    }
                }
            });
        },

        parse: function() {

            var $this = this;

            $this.program = $this.settings.el.val().trim();

            if ($this.program) {

                $.each($this.program.split(''), function(index, character) {
                    $this.buffer += character;
                    $this._tokenize();
                });
            }

            $this._interpretAll();
        }
    }

})(jQuery);