<?php
    /**
     * @class  apicenter
     * @author NHN (developers@xpressengine.com)
     * @brief basic class of the apicenter module
     **/

    class apicenter extends ModuleObject {

        /**
         * @brief Implement if additional tasks are necessary when installing
         **/
        function moduleInstall() {
            // Registered in action forward
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('apicenter', 'view', 'IS');

            return new Object();
        }

        /**
         * @brief a method to check if successfully installed
         **/
        function checkUpdate() {
            return false;
        }

        /**
         * @brief Execute update
         **/
        function moduleUpdate() {
            return new Object(0, 'success_updated');
        }

        /**
         * @brief Re-generate the cache file
         **/
        function recompileCache() {
        }
    }
?>
