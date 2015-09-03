<?php
namespace framework
{
    class View
    {
        function getOverview($arguments=array())
        {
            $overviewTable = $this->getOverviewTable($arguments);
            return $overviewTable->renderOverview();
        }

        function getEdit($arguments=array())
        {
            if (count($arguments) > 0)
            {
                $key = array_shift($arguments);
                $overviewTable = $this->getOverviewTable($arguments);
                return $overviewTable->renderEditForm($key);
            }

            return $this->getOverview();
        }

        function getDelete($arguments=array())
        {
            if (count($arguments) > 0)
            {
                $key = array_shift($arguments);
                $overviewTable = $this->getOverviewTable($arguments);
                $overviewTable->delete($key);
            }

            return $this->getOverview();
        }

        function getSort($arguments=array())
        {
            if (count($arguments) > 1)
            {
                $key = array_shift($arguments);
                $direction = array_shift($arguments);
                $overviewTable = $this->getOverviewTable($arguments);
                $overviewTable->sortorder($key, $direction);
            }

            return $this->getOverview();
        }

        /**
         * Get overview table
         *  OVERRIDE DEZE FUNCTIE!!
         * @return \OverviewTable
         */
        protected function getOverviewTable($arguments=array())
        {
            return null;
        }
    }
}