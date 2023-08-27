<?php
namespace SabaiApps\Directories\Component\Voting\Model;

class VoteGateway extends Base\VoteGateway
{
    public function getResults($bundleName, $entityId, $fieldName)
    {      
        $sql = sprintf(
            'SELECT vote_entity_id, vote_name, COUNT(*) AS cnt, SUM(vote_value) AS sm, MAX(vote_created) AS mx'
                . ' FROM %svoting_vote WHERE vote_bundle_name = %s AND vote_entity_id IN (%s) AND vote_field_name = %s'
                . ' GROUP BY vote_name',
             $this->_db->getResourcePrefix(),
             $this->_db->escapeString($bundleName),
             implode(',', array_map(array($this->_db, 'escapeString'), (array)$entityId)),
             $this->_db->escapeString($fieldName)
        );
        $rs = $this->_db->query($sql);
        $ret = [];
        foreach ($rs as $row) {
            $ret[$row['vote_entity_id']][$row['vote_name']] = array('count' => (int)$row['cnt'], 'sum' => $row['sm'], 'last_voted_at' => $row['mx']);
        }
        
        return is_array($entityId) ? $ret : (isset($ret[$entityId]) ? $ret[$entityId] : null);
    }
    
    public function getVotes($bundleName, array $entityIds, $userId, array $fieldNames = null)
    {
        $sql = sprintf(
            'SELECT vote_field_name, vote_entity_id, vote_value FROM %svoting_vote WHERE vote_bundle_name = %s AND vote_entity_id IN (%s) AND vote_user_id = %d %s %s',
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($bundleName),
            implode(',', array_map('intval', $entityIds)),
            $userId,
            isset($fieldNames) ? sprintf('AND vote_field_name IN (%s)', implode(',', array_map(array($this->_db, 'escapeString'), $fieldNames))) : '',
            empty($userId) ? sprintf('AND vote_ip = %s', $this->_db->escapeString($this->_getIp())) : ''
        );
        $rs = $this->_db->query($sql);
        $ret = [];
        foreach ($rs as $row) {
            $ret[$row['vote_field_name']][$row['vote_entity_id']] = $row['vote_value'];
        }
        return $ret;
    }
    
    public function countByLevel($bundleName, $entityId, $fieldName, $name)
    {
        $sql = sprintf(
            'SELECT vote_level, COUNT(*) AS cnt
FROM %svoting_vote
WHERE vote_bundle_name = %s AND vote_entity_id = %d AND vote_field_name = %s AND vote_name = %s
GROUP BY vote_level',
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($bundleName),
            $entityId,
            $this->_db->escapeString($fieldName),
            $this->_db->escapeString($name)
        );
        $rs = $this->_db->query($sql);
        $ret = [];
        foreach ($rs as $row) {
            $ret[$row['vote_level']] = $row['cnt'];
        }
        return $ret;
    }
    
    private function _getIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        return '';
    }

    public function getMissingEntityIds($tableName, $idKey, $bundleNameKey = null, $bundleName = null, $statusKey = null, $status = null, $isReference = false)
    {
        $where = [];
        if ($bundleNameKey) {
            $where[] = sprintf('p.%s = %s', $bundleNameKey, $this->_db->escapeString($bundleName));
        }
        if ($statusKey) {
            $where[] = sprintf('p.%s = %s', $statusKey, $this->_db->escapeString($status));
        }
        $vote_id_key = $isReference ? 'vote_reference_id' : 'vote_entity_id';
        $where[] = 'v.' . $vote_id_key . ' IS NULL';
        $sql = sprintf(
            'SELECT p.%2$s FROM %1$s p 
LEFT JOIN %3$svoting_vote v ON p.%2$s = v.%4$s 
WHERE %5$s',
            $tableName,
            $idKey,
            $this->_db->getResourcePrefix(),
            $isReference ? 'vote_reference_id' : 'vote_entity_id',
            implode(' AND ', $where)
        );
        $rs = $this->_db->query($sql);
        $ret = [];
        foreach ($rs as $row) {
            $ret[] = $row[$idKey];
        }
        return $ret;
    }

    public function deleteEntityVotes($tableName, $idKey, $bundleNameKey = null, $bundleName = null, $statusKey = null, $status = null, $isReference = false)
    {
        $where = [];
        if ($bundleNameKey) {
            $where[] = sprintf('p.%s = %s', $bundleNameKey, $this->_db->escapeString($bundleName));
        }
        if ($statusKey) {
            $where[] = sprintf('p.%s = %s', $statusKey, $this->_db->escapeString($status));
        }
        $sql = sprintf(
            'DELETE FROM %3$svoting_vote WHERE %4$s IN (SELECT p.%2$s FROM %1$s p %5$s)',
            $tableName,
            $idKey,
            $this->_db->getResourcePrefix(),
            $isReference ? 'vote_reference_id' : 'vote_entity_id',
            empty($where) ? '' : 'WHERE ' . implode(' AND ', $where)
        );
        $this->_db->exec($sql);
    }

    public function deleteReferenceVotes($name, $value = 0)
    {
        $sql = sprintf(
            'DELETE FROM %1$svoting_vote WHERE vote_reference_id IN (SELECT vote_reference_id from (SELECT vote_reference_id FROM %1$svoting_vote WHERE vote_name = %2$s AND vote_value = %3$F) AS tmp)',
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($name),
            $value
        );
        $this->_db->exec($sql);
    }
}