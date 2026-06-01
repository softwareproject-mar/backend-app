/* Laravel Sanctum — jalankan sekali di Firebird jika tabel belum ada (error -204 PERSONAL_ACCESS_TOKENS). */

CREATE TABLE personal_access_tokens (
    id BIGINT NOT NULL,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name BLOB SUB_TYPE TEXT NOT NULL,
    token VARCHAR(64) NOT NULL,
    abilities BLOB SUB_TYPE TEXT,
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT PK_PAT PRIMARY KEY (id),
    CONSTRAINT UQ_PAT_TOK UNIQUE (token)
);

CREATE GENERATOR GEN_personal_access_tokens_id;
SET GENERATOR GEN_personal_access_tokens_id TO 0;

SET TERM ^ ;
CREATE TRIGGER TRG_personal_access_tokens_id FOR personal_access_tokens
ACTIVE BEFORE INSERT POSITION 0
AS BEGIN
  IF (NEW.id IS NULL) THEN
    NEW.id = GEN_ID(GEN_personal_access_tokens_id, 1);
END^
SET TERM ; ^
