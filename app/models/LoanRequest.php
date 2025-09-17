<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


class LoanRequest extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DECLINED = 'declined';

    public static function tableName(): string
    {
        return 'loan_requests';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'amount', 'term'], 'required'],
            [['user_id', 'amount', 'term'], 'integer'],
            [['amount'], 'integer', 'min' => 1],
            [['term'], 'integer', 'min' => 1],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_DECLINED]],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'user_id' => 'ID пользователя',
            'amount' => 'Сумма',
            'term' => 'Срок',
            'status' => 'Статус',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    public static function getStatusList(): array
    {
        return [
            self::STATUS_PENDING => 'В ожидании',
            self::STATUS_APPROVED => 'Одобрено',
            self::STATUS_DECLINED => 'Отклонено',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function approve(): bool
    {
        $this->status = self::STATUS_APPROVED;
        return $this->save(false);
    }

    public function decline(): bool
    {
        $this->status = self::STATUS_DECLINED;
        return $this->save(false);
    }

    public static function findPendingRequests(): array
    {
        return self::find()
            ->where(['status' => self::STATUS_PENDING])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
    }

    public static function hasApprovedLoan(int $userId): bool
    {
        return self::find()
            ->where([
                'user_id' => $userId,
                'status' => self::STATUS_APPROVED
            ])
            ->exists();
    }

    public function beforeSave($insert): bool
    {
        if ($insert && $this->status === self::STATUS_APPROVED) {
            if (self::hasApprovedLoan($this->user_id)) {
                $this->addError('user_id', 'У пользователя уже есть одобренный займ');
                return false;
            }
        }

        return parent::beforeSave($insert);
    }
}