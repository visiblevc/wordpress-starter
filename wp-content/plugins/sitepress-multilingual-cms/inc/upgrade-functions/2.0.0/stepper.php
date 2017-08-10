<?php

class Icl_Stepper
{

    /**
     * Added dummy element to match number of elements and to cover init action
     * @var array
     */
    protected $_steps = array(NULL);
    /**
     * Current step
     * @var int
     */
    protected $_step;
    /**
     * Next step can be forced with Icl_Stepper::setNextStep()
     * @var <type>
     */
    protected $_nextStep = NULL;

    /**
     * Provide current step here
     * @param int $step
     */
    function __construct($step = 0) {
        if (empty($step)) {
            $step = 0;
        }
        $this->_step = intval($step);
    }

    /**
     * Register steps (function names)
     */
    public function registerSteps() {
        $args = func_get_args();
        $this->_steps = array_merge($this->_steps, $args);
    }

    /**
     * Returns current step
     * @return int
     */
    public function getStep() {
        return $this->_step;
    }

    /**
     * Returns next step
     * @return int
     */
    public function getNextStep() {
        return !is_null($this->_nextStep)? $this->_nextStep : $this->_step += 1;
    }

    /**
     * Sets current step
     * @param int $num
     */
    public function setStep($num) {
        $this->_step = intval($num);
    }

    /**
     * Forcing next step
     * @param int $num
     */
    public function setNextStep($num) {
        $this->_nextStep = intval($num);
    }

    /**
     * Calculates bar width
     * @return int Should be used as percentage width (%)
     */
    public function barWidth() {
        return round(($this->_step*100)/count($this->_steps));
    }

    /**
     * Calls current step's function
     * @return mixed
     */
    public function init() {
        if ($this->_step !== 0 && isset($this->_steps[$this->_step])
                && is_callable($this->_steps[$this->_step])) {
            return call_user_func_array($this->_steps[$this->_step], array($this->_step, $this));
        }
    }

    /**
     * Returns initial HTML formatted screen
     * @param string $message Message to be displayed
     * @return string
     */
    public function render($message = '') {
        $output = '<div class="progress-bar" style="width: 100%; height: 15px; background-color: #FFFFFFF; border: 1px solid #e6db55;">
            <div class="progress" style="height: 100%; background-color: Yellow; width: ' . $this->barWidth() . '%;"></div>
        </div>
        <div class="message" style="clear:both;">' . $message . '</div>';
        return $output;
    }

}