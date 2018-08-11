<?php

declare(strict_types=1);

use yii\db\Migration;

class m180810_192900_create_table_podium_post extends Migration
{
    public function up(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%podium_post}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'forum_id' => $this->integer()->notNull(),
            'thread_id' => $this->integer()->notNull(),
            'author_id' => $this->integer()->notNull(),
            'content' => $this->text()->notNull(),
            'edited' => $this->boolean()->notNull()->defaultValue(false),
            'likes' => $this->integer()->notNull()->defaultValue(0),
            'dislikes' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'edited_at' => $this->integer(),
        ], $tableOptions);

        $this->addForeignKey('fk-podium_post-author_id', '{{%podium_post}}', 'author_id', '{{%podium_member}}', 'id', 'NO ACTION', 'CASCADE');
        $this->addForeignKey('fk-podium_post-category_id', '{{%podium_post}}', 'category_id', '{{%podium_category}}', 'id', 'NO ACTION', 'CASCADE');
        $this->addForeignKey('fk-podium_post-forum_id', '{{%podium_post}}', 'forum_id', '{{%podium_forum}}', 'id', 'NO ACTION', 'CASCADE');
        $this->addForeignKey('fk-podium_post-thread_id', '{{%podium_post}}', 'thread_id', '{{%podium_thread}}', 'id', 'NO ACTION', 'CASCADE');
    }

    public function down(): void
    {
        $this->dropTable('{{%podium_post}}');
    }
}