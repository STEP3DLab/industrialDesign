import http.cookiejar, urllib.request, urllib.parse, urllib.error, re
BASE='http://localhost:8080'
def login(user):
    jar=http.cookiejar.CookieJar()
    client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    page=client.open(BASE+'/login/index.php').read().decode()
    token=re.search(r'name="logintoken" value="([^"]+)"',page).group(1)
    data=urllib.parse.urlencode({'username':user,'password':'Test-only-72Hours!','logintoken':token}).encode()
    result=client.open(BASE+'/login/index.php',data).read().decode()
    assert 'loginerrors' not in result
    return client
student=login('ci_student')
portal=student.open(BASE+'/local/technopark/index.php').read().decode()
assert 'Цифровая' in portal and 'lab-hero' in portal
courseid=re.search(r'/course/view.php\?id=(\d+)',portal).group(1)
course=student.open(BASE+'/course/view.php?id='+courseid).read().decode()
assert 'Самопроверка' in course
assert 'Тема 1.2 · подготовка материала' not in course
try:
    response=student.open(BASE+'/local/technopark/report.php?id='+courseid)
except urllib.error.HTTPError as error:
    assert error.code in (403,404), error.code
    response=error
report=response.read().decode()
assert 'Скачать CSV' not in report
assert 'nopermissions' in report or 'required_capability_exception' in report or 'недостаточно прав' in report.lower()
teacher=login('ci_editingteacher')
report=teacher.open(BASE+'/local/technopark/report.php?id='+courseid).read().decode()
assert 'Скачать CSV' in report and 'CI student' in report
print('PASS: login, portal, course, draft visibility, report access via HTTP')
