<?php
namespace anoweco;

class Storage
{
    public function __construct()
    {
        require __DIR__ . '/../../data/config.php';
        $this->db = new \PDO($dbdsn, $dbuser, $dbpass);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Store a new comment into the database
     *
     * @param object  $json   Micropub create JSON
     * @param integer $userId ID of the user whom this comment belongs
     *
     * @return integer Comment ID
     * @throws \Exception
     */
    public function addComment($json, $userId)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comments SET'
            . '  comment_user_id = :userId'
            . ', comment_published = NOW()'
            . ', comment_of_url = :ofUrl'
            . ', comment_type = :type'
            . ', comment_json = :json'
        );

        $ofUrl = '';
        $type  = null;
        if (isset($json->properties->{'in-reply-to'})) {
            $ofUrl = reset($json->properties->{'in-reply-to'});
            $type = 'reply';
        } else if (isset($json->properties->{'like-of'})) {
            $ofUrl = reset($json->properties->{'like-of'});
            $type  = 'like';
        } else {
            throw new \Exception(
                'Invalid post type, only reply and like allowed',
                400
            );
        }

        $stmt->execute(
            array(
                ':userId' => $userId,
                ':ofUrl'  => $ofUrl,
                ':type'   => $type,
                ':json'   => json_encode($json),
            )
        );
        return $this->db->lastInsertId();
    }

    /**
     * @return null|object NULL if not found, JSON comment object otherwise
     *                     - "Xrow" property contains the database row object
     *                     - "user" property contains the user db row object
     */
    public function getJsonComment($id)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM comments WHERE comment_id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetchObject();

        if ($row === false) {
            return null;
        }

        $json = json_decode($row->comment_json);
        $json->Xrow = $row;
        //FIXME: load user

        $stmt = $this->db->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$row->comment_user_id]);
        $rowUser = $stmt->fetchObject();
        if ($rowUser === false) {
            $rowUser = (object) array(
                'user_id'       => 0,
                'user_name'     => 'Anonymous',
                'user_imageurl' => '',
            );
        }

        $json->user = $rowUser;
        return $json;
    }

    /**
     * @return null|object NULL if not found, JSON comment object otherwise
     *                     - "Xrow" property contains the database row object
     *                     - "user" property contains the user db row object
     */
    public function listLatest()
    {
        $stmt = $this->db->prepare(
            'SELECT comment_id, comment_user_id, comment_published'
            . ', comment_of_url, comment_type'
            . ' FROM comments'
            . ' ORDER BY comment_published DESC'
            . ' LIMIT 20'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        if (!count($rows)) {
            return [];
        }
        $userIds = array_values(
            array_unique(array_column($rows, 'comment_user_id'))
        );


        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE user_id IN (' . $placeholders . ')'
        );
        $stmt->execute($userIds);

        $users = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $users = array_combine(
            array_column($users, 'user_id'),
            $users
        );

        foreach ($rows as $row) {
            $row->user = $users[$row->comment_user_id];
        }

        return $rows;
    }

    /**
     * @return null|object NULL if not found, user database row otherwise
     */
    public function getUser($id)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE user_id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetchObject();

        if ($row === false) {
            return null;
        }
        return $row;
    }

    public function findUser($name, $imageurl)
    {
        $stmt = $this->db->prepare(
            'SELECT user_id FROM users'
            . ' WHERE user_name = ? AND user_imageurl = ?'
        );
        $stmt->execute([$name, $imageurl]);
        $row = $stmt->fetchObject();

        if ($row === false) {
            return null;
        }
        return $row->user_id;
    }

    public function createUser($name, $imageurl)
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users SET'
            . '  user_name = :name'
            . ', user_imageurl = :imageurl'
        );
        $stmt->execute(
            array(
                ':name'     => $name,
                ':imageurl' => $imageurl,
            )
        );
        return $this->db->lastInsertId();
    }

    public function setPostPingState($postId, $pingstate)
    {
        $stmt = $this->db->prepare(
            'UPDATE comments SET comment_pingstate = ? WHERE comment_id = ?'
        );
        $stmt->execute(array($pingstate, $postId));
    }
}
?>
