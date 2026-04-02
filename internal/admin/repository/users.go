package repository

import (
	"context"
	"fmt"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5/pgxpool"

	"github.com/skyplix/zai-tds/internal/model"
)

// UserRepository handles SQL operations for the users table.
type UserRepository struct {
	db *pgxpool.Pool
}

// NewUserRepository creates a new repository.
func NewUserRepository(db *pgxpool.Pool) *UserRepository {
	return &UserRepository{db: db}
}

// List returns a paginated list of users.
func (r *UserRepository) List(ctx context.Context, limit, offset int) ([]model.User, error) {
	rows, err := r.db.Query(ctx, `
		SELECT id, login, role, state, api_key
		FROM users
		ORDER BY created_at DESC
		LIMIT $1 OFFSET $2
	`, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("query users: %w", err)
	}
	defer rows.Close()

	var users []model.User
	for rows.Next() {
		var u model.User
		err := rows.Scan(&u.ID, &u.Login, &u.Role, &u.State, &u.ApiKey)
		if err != nil {
			return nil, fmt.Errorf("scan user: %w", err)
		}
		users = append(users, u)
	}

	return users, nil
}

// GetByID retrieves a single user by uuid.
func (r *UserRepository) GetByID(ctx context.Context, id uuid.UUID) (*model.User, error) {
	var u model.User
	err := r.db.QueryRow(ctx, `
		SELECT id, login, role, state, api_key
		FROM users
		WHERE id = $1
	`, id).Scan(&u.ID, &u.Login, &u.Role, &u.State, &u.ApiKey)
	if err != nil {
		return nil, fmt.Errorf("get user: %w", err)
	}
	return &u, nil
}

// Create inserts a new user. Password hashing should be done before calling this or inside.
// For now we assume the handler handles hashing if needed, or we use a separate method.
func (r *UserRepository) Create(ctx context.Context, u *model.User, passwordHash string) error {
	if u.ID == uuid.Nil {
		u.ID = uuid.New()
	}

	return r.db.QueryRow(ctx, `
		INSERT INTO users (id, login, password_hash, role, state, api_key)
		VALUES ($1, $2, $3, $4, $5, $6)
		RETURNING id
	`, u.ID, u.Login, passwordHash, u.Role, u.State, u.ApiKey).Scan(&u.ID)
}

// Update modifies an existing user.
func (r *UserRepository) Update(ctx context.Context, u *model.User) error {
	_, err := r.db.Exec(ctx, `
		UPDATE users
		SET login = $2, role = $3, state = $4, api_key = $5, updated_at = NOW()
		WHERE id = $1
	`, u.ID, u.Login, u.Role, u.State, u.ApiKey)
	return err
}

// Delete removes a user.
func (r *UserRepository) Delete(ctx context.Context, id uuid.UUID) error {
	_, err := r.db.Exec(ctx, "DELETE FROM users WHERE id = $1", id)
	return err
}
