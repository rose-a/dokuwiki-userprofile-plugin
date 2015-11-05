CREATE TABLE fields (fid INTEGER PRIMARY KEY, name, title, defaultval);
CREATE UNIQUE INDEX idx_fields ON fields(name);
