<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%loan_requests}}`.
 */
class m240101_000001_create_loan_requests_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%loan_requests}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'amount' => $this->integer()->notNull(),
            'term' => $this->integer()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'created_at' => $this->timestamp()->defaultExpression('NOW()'),
            'updated_at' => $this->timestamp()->defaultExpression('NOW()'),
        ]);

        // Add check constraints
        $this->execute("ALTER TABLE {{%loan_requests}} ADD CONSTRAINT chk_amount CHECK (amount > 0)");
        $this->execute("ALTER TABLE {{%loan_requests}} ADD CONSTRAINT chk_term CHECK (term > 0)");
        $this->execute("ALTER TABLE {{%loan_requests}} ADD CONSTRAINT chk_status CHECK (status IN ('pending', 'approved', 'declined'))");

        // Create indexes for better performance
        $this->createIndex(
            'idx-loan_requests-user_id',
            '{{%loan_requests}}',
            'user_id'
        );

        $this->createIndex(
            'idx-loan_requests-status',
            '{{%loan_requests}}',
            'status'
        );

        $this->createIndex(
            'idx-loan_requests-created_at',
            '{{%loan_requests}}',
            'created_at'
        );

        $this->createIndex(
            'idx-loan_requests-user_status',
            '{{%loan_requests}}',
            ['user_id', 'status']
        );

        // Create function for updating updated_at timestamp
        $this->execute("
            CREATE OR REPLACE FUNCTION update_updated_at_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            $$ language 'plpgsql';
        ");

        // Create trigger for automatic updated_at update
        $this->execute("
            CREATE TRIGGER update_loan_requests_updated_at
                BEFORE UPDATE ON {{%loan_requests}}
                FOR EACH ROW
                EXECUTE FUNCTION update_updated_at_column();
        ");

        // Insert test data
        $this->batchInsert('{{%loan_requests}}', 
            ['user_id', 'amount', 'term', 'status'],
            [
                [1, 5000, 30, 'pending'],
                [2, 10000, 60, 'pending'],
                [3, 3000, 15, 'approved'],
                [4, 7500, 45, 'declined'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop trigger
        $this->execute('DROP TRIGGER IF EXISTS update_loan_requests_updated_at ON {{%loan_requests}}');
        
        // Drop function
        $this->execute('DROP FUNCTION IF EXISTS update_updated_at_column()');
        
        // Drop table
        $this->dropTable('{{%loan_requests}}');
    }
}