' ============================================================
' leer_ves.vbs — Lee tablas de un MDB Jet 3.x (Access 97)
' Uso: cscript //nologo leer_ves.vbs "ruta.mdb" "NombreTabla" ["filtro WHERE"]
' Salida: JSON array en stdout  /  errores en stderr
' ============================================================
Dim args
Set args = WScript.Arguments

If args.Count < 2 Then
    WScript.StdErr.Write "USO: leer_ves.vbs ruta.mdb NombreTabla [filtroWHERE]"
    WScript.Echo "[]"
    WScript.Quit 1
End If

Dim mdbPath, tableName, extraFilter
mdbPath     = args(0)
tableName   = args(1)
extraFilter = ""
If args.Count > 2 Then extraFilter = args(2)

' ── Abrir base de datos con DAO 3.6 (soporta Jet 3.x / Access 97) ────────────
Dim dbe, db
On Error Resume Next
Set dbe = CreateObject("DAO.DBEngine.36")
If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR_DAO:" & Err.Description
    WScript.Echo "[]"
    WScript.Quit 2
End If

Set db = dbe.OpenDatabase(mdbPath, False, True)   ' False=no exclusive, True=read-only
If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR_OPEN:" & Err.Description
    WScript.Echo "[]"
    WScript.Quit 3
End If
On Error GoTo 0

' ── Construir SQL ─────────────────────────────────────────────────────────────
Dim sql
sql = "SELECT * FROM [" & tableName & "]"
If extraFilter <> "" Then sql = sql & " WHERE " & extraFilter

Dim rs
On Error Resume Next
Set rs = db.OpenRecordset(sql, 4)   ' 4 = dbOpenSnapshot (read-only, fast)
If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR_QUERY:" & Err.Description & " SQL:" & sql
    WScript.Echo "[]"
    db.Close
    WScript.Quit 4
End If
On Error GoTo 0

' ── Serializar a JSON ────────────────────────────────────────────────────────
' Construimos campo a campo; los valores numéricos sin comillas
Dim out, firstRow, f, val, vt, encoded
out      = "["
firstRow = True

Do While Not rs.EOF
    If Not firstRow Then out = out & ","
    firstRow = False
    out = out & "{"

    Dim firstField
    firstField = True
    For Each f In rs.Fields
        If Not firstField Then out = out & ","
        firstField = False

        val = f.Value
        vt  = VarType(val)

        out = out & Chr(34) & f.Name & Chr(34) & ":"

        If IsNull(val) Then
            out = out & "null"
        ElseIf vt = 8 Then                          ' vbString
            encoded = val
            encoded = Replace(encoded, "\",  "\\")
            encoded = Replace(encoded, Chr(34), "\" & Chr(34))
            encoded = Replace(encoded, Chr(13), "")
            encoded = Replace(encoded, Chr(10), "\n")
            encoded = Replace(encoded, Chr(9),  "\t")
            out = out & Chr(34) & Trim(encoded) & Chr(34)
        ElseIf vt = 11 Then                         ' vbBoolean
            If val Then out = out & "true" Else out = out & "false"
        ElseIf vt = 7 Then                          ' vbDate
            out = out & Chr(34) & CStr(val) & Chr(34)
        Else
            out = out & CStr(val)
        End If
    Next

    out = out & "}"
    rs.MoveNext
Loop

out = out & "]"

rs.Close
db.Close

WScript.Echo out
WScript.Quit 0
