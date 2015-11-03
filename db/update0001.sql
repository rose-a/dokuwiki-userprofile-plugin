CREATE TABLE fields (fid INTEGER PRIMARY KEY, name, title, default);
CREATE UNIQUE INDEX idx_fields ON fields(name);
