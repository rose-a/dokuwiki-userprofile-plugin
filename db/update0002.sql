CREATE TABLE users ([uid] INTEGER PRIMARY KEY, [user], [name], [email]);
CREATE UNIQUE INDEX idx_users ON users([name]);
CREATE TABLE fieldvals ([vid] INTEGER PRIMARY KEY, [fid] INTEGER, [uid] INTEGER, [value]);