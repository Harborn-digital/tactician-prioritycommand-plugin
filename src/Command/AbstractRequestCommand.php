<?php

namespace ConnectHolland\Tactician\PriorityPlugin\Command;

/**
 * Command that may be executed at any time during the current request
 *
 * Useful for anything you can postpone until after you've send the response to the user
 *
 * @author Ron Rademaker
 */
abstract class AbstractRequestCommand implements PriorityCommandInterface {

    /**
     * getQueue
     *
     * Gets the queue to put the command in
     *
     * @since 1.0
     * @access public
     * @return string
     * */
    public function getQueue() {
        return "request";
    }

}
