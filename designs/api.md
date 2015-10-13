# 仪器控用户管理

## 接口设计

namespace `YiQiKong/User`

### 注册新用户

#### 调用函数

`YiQiKong/User/Signup(object data)`

#### 参数列表

```
	data    object    具体信息, 结构如下:

	{
		"name":                  // 姓名   必填
		"gender":                // 性别
		"email":                 // 邮箱   必填
		"phone":                 // 电话   必填
		"password":              // 密码   必填
		"identity":              // 身份证号
		"residence":             // 用户居住地
		"institution":           // 用户所属机构   必填
		"initials":              // 用户名首字符
		"icon":                  // 用户头像在yiqikong_web的路径
		"gapper_id":             // gapper_id   必填
		"ctime":                 // 用户注册时间
		"atime":                 // web 注册用户的激活时间
		"wechat_bind_status":    // 微信绑定状态，1 绑定， 0 未绑定
		"wechat_openid":         // 用户绑定微信后其微信账号id
		"lab_id":                // 用户为自己的仪器建站后的站点id
		"is_admin":              // 用户是否为某个站点的管理员
	}
```

#### 返回结果

```
    如传递参数错误, 或者操作失败, 则会抛出 exception, exception 错误码、错误信息如下
    {
        1001: "异常参数传入",
    }

    true     bool        表示用户注册成功

```

### 新注册用户激活

#### 调用函数

``

#### 参数列表

```

```

#### 返回结果

```

```

### 用户登录

#### 调用函数

``

#### 参数列表

```
	
```

#### 返回结果

```
	
```

### 获取用户信息

#### 调用函数

```

```

#### 返回结果

```

```

